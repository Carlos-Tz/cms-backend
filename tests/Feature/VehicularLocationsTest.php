<?php

use App\Models\User;
use Database\Seeders\GnuVehicularBlueprintSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GnuVehicularBlueprintSeeder::class);
    $this->token = JWTAuth::fromUser(User::factory()->create());
});

it('returns the locations column with its hero and map', function () {
    $this->getJson('/api/public/gnu-vehicular/pages/ubicaciones')
        ->assertOk()
        ->assertJsonPath('data.title', 'Ubicaciones')
        ->assertJsonCount(2, 'data.sections')
        ->assertJsonPath('data.sections.0.type', 'page_hero')
        ->assertJsonPath('data.sections.1.type', 'stations_map');
});

it('returns each open child page as hero plus rich text', function (string $slug, string $title) {
    $this->getJson("/api/public/gnu-vehicular/pages/{$slug}")
        ->assertOk()
        ->assertJsonPath('data.title', $title)
        ->assertJsonPath('data.path', "/ubicaciones/{$slug}")
        ->assertJsonCount(2, 'data.sections')
        ->assertJsonPath('data.sections.0.type', 'page_hero')
        ->assertJsonPath('data.sections.1.type', 'rich_text');
})->with([
    ['estaciones-morelia', 'Estaciones Morelia'],
    ['estacion-uruapan', 'Estación Uruapan'],
    ['talleres', 'Talleres'],
]);

it('returns the scheduling page with the widget section', function () {
    $this->getJson('/api/public/gnu-vehicular/pages/agenda-tu-verificacion')
        ->assertOk()
        ->assertJsonCount(2, 'data.sections')
        ->assertJsonPath('data.sections.1.type', 'service_scheduling')
        ->assertJsonPath('data.sections.1.content.title', 'Agenda tu verificación')
        // The bookable hours are not content: they come from the availability endpoint.
        ->assertJsonMissingPath('data.sections.1.content.slots');
});

it('nests the four child pages under the locations column in the menu', function () {
    $this->getJson('/api/public/gnu-vehicular/menu')
        ->assertOk()
        ->assertJsonPath('data.1.title', 'Ubicaciones')
        ->assertJsonCount(4, 'data.1.children')
        ->assertJsonPath('data.1.children.0.title', 'Estaciones Morelia')
        ->assertJsonPath('data.1.children.1.title', 'Estación Uruapan')
        ->assertJsonPath('data.1.children.2.title', 'Agenda tu verificación')
        ->assertJsonPath('data.1.children.3.title', 'Talleres');
});

it('lets the admin write the workshops copy', function () {
    $section = vehicularSection('rich_text', 'talleres');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => ['body' => '<p>Taller autorizado en Morelia.</p>'],
        ])
        ->assertOk()
        ->assertJsonPath('data.content.body', '<p>Taller autorizado en Morelia.</p>');
});

it('lets the admin edit the scheduling copy', function () {
    $section = vehicularSection('service_scheduling');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'title' => 'Agenda en línea',
                'success_message' => '¡Listo! Te esperamos.',
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.content.title', 'Agenda en línea');

    expect(vehicularSection('service_scheduling')->content['success_message'])
        ->toBe('¡Listo! Te esperamos.');
});

it('rejects scheduling copy without a success message', function () {
    $section = vehicularSection('service_scheduling');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => ['title' => 'Agenda en línea'],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['content.success_message']);
});
