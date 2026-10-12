<?php

use App\Models\Site;
use App\Models\Station;
use App\Models\User;
use Database\Seeders\GnuBlueprintSeeder;
use Database\Seeders\GnuVehicularBlueprintSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GnuBlueprintSeeder::class);
    // A second site, to prove the public listing never leaks across sites.
    $this->seed(GnuVehicularBlueprintSeeder::class);
    $this->site = Site::where('slug', 'gnu')->firstOrFail();
    $this->token = JWTAuth::fromUser(User::factory()->create());
});

it('lists the active stations of a site for the public map', function () {
    Station::factory()->for($this->site)->inactive()->create(['name' => 'Estación cerrada']);

    $response = $this->getJson('/api/public/gnu/stations')
        ->assertOk()
        ->assertJsonPath('meta.site', 'gnu')
        ->assertJsonPath('meta.total', 2);

    expect(collect($response->json('data'))->pluck('name'))
        ->not->toContain('Estación cerrada');

    $response->assertJsonFragment([
        'name' => 'Estación Guadalupe',
        'latitude' => 25.6768,
        'longitude' => -100.256,
    ]);
});

it('orders public stations by descending ID', function () {
    $olderStation = Station::factory()->for($this->site)->create(['name' => 'Estación Alfa anterior']);
    $newerStation = Station::factory()->for($this->site)->create(['name' => 'Estación Zeta reciente']);

    $names = collect($this->getJson('/api/public/gnu/stations')->assertOk()->json('data'))
        ->pluck('name')
        ->take(2)
        ->values()
        ->all();

    expect($names)->toBe([$newerStation->name, $olderStation->name]);
});

it('does not leak the stations of another site', function () {
    $other = Site::where('slug', 'gnu-vehicular')->firstOrFail();
    Station::factory()->for($other)->create(['name' => 'Estación Morelia']);

    $names = collect($this->getJson('/api/public/gnu/stations')->assertOk()->json('data'))
        ->pluck('name');

    expect($names)->not->toContain('Estación Morelia')->toHaveCount(2);
});

it('creates a station through the admin API', function () {
    $this->withToken($this->token)
        ->postJson('/api/admin/stations', [
            'site_id' => $this->site->id,
            'name' => 'Estación Apodaca',
            'address' => 'Carretera Miguel Alemán km 20, Apodaca, N.L.',
            'latitude' => 25.7810,
            'longitude' => -100.1880,
            'phone' => null,
            'schedule' => null,
            'is_active' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Estación Apodaca')
        ->assertJsonPath('meta.message', 'Estación creada correctamente');

    expect($this->site->stations()->count())->toBe(3);
});

it('rejects coordinates outside the world', function () {
    $this->withToken($this->token)
        ->postJson('/api/admin/stations', [
            'site_id' => $this->site->id,
            'name' => 'Estación imposible',
            'address' => 'En ningún lado',
            'latitude' => 120,
            'longitude' => -400,
            'is_active' => true,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'latitude' => 'La latitud debe estar entre -90 y 90.',
            'longitude' => 'La longitud debe estar entre -180 y 180.',
        ]);
});

it('does not let a station change site', function () {
    $station = Station::factory()->for($this->site)->create();
    $other = Site::where('slug', 'gnu-vehicular')->firstOrFail();

    $this->withToken($this->token)
        ->putJson("/api/admin/stations/{$station->id}", [
            'site_id' => $other->id,
            'name' => $station->name,
            'address' => $station->address,
            'latitude' => $station->latitude,
            'longitude' => $station->longitude,
            'is_active' => true,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['site_id' => 'Una estación no puede cambiar de sitio.']);
});

it('hides a station from the public map without deleting it', function () {
    $station = $this->site->stations()->firstOrFail();

    $this->withToken($this->token)
        ->putJson("/api/admin/stations/{$station->id}", [
            'name' => $station->name,
            'address' => $station->address,
            'latitude' => $station->latitude,
            'longitude' => $station->longitude,
            'phone' => $station->phone,
            'schedule' => $station->schedule,
            'is_active' => false,
        ])
        ->assertOk();

    $this->getJson('/api/public/gnu/stations')->assertOk()->assertJsonPath('meta.total', 1);
    expect($this->site->stations()->count())->toBe(2);
});

it('deletes a station', function () {
    $station = $this->site->stations()->firstOrFail();

    $this->withToken($this->token)
        ->deleteJson("/api/admin/stations/{$station->id}")
        ->assertOk()
        ->assertJsonPath('meta.message', 'Estación eliminada correctamente');

    expect($this->site->stations()->count())->toBe(1);
});

it('blocks station writes without a token', function () {
    $this->postJson('/api/admin/stations', [])
        ->assertUnauthorized()
        ->assertJsonPath('meta.message', 'No autenticado');
});

it('lets the admin move the map of a stations_map section', function () {
    $section = $this->site->sections()->where('type', 'stations_map')->firstOrFail();

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            // Pasted the way Google Maps copies it, with a space after the comma.
            'content' => ['title' => 'Encuentra tu estación', 'center' => '25.6866, -100.3161', 'zoom' => '13'],
        ])
        ->assertOk()
        ->assertJsonPath('data.content.title', 'Encuentra tu estación')
        ->assertJsonPath('data.content.center', '25.6866, -100.3161')
        ->assertJsonPath('data.content.zoom', '13');
});

it('rejects a map center or zoom the map cannot draw', function (string $center) {
    $section = $this->site->sections()->where('type', 'stations_map')->firstOrFail();

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => ['title' => 'Mapa', 'center' => $center, 'zoom' => '23'],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'content.center' => 'El centro del mapa debe tener el formato «latitud,longitud», con la latitud entre -90 y 90 y la longitud entre -180 y 180.',
            'content.zoom' => 'El zoom del mapa debe ser un número entero entre 0 y 22.',
        ]);
})->with(['90.5,-100.3094', '25.6714,-180.5', '25.6714', 'Monterrey']);
