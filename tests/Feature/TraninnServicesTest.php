<?php

use App\Models\Station;
use App\Models\User;
use Database\Seeders\TraninnLogisticBlueprintSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

// `traninnSection()` lives in TraninnHomeTest: Pest helpers are global.

beforeEach(function () {
    $this->seed(TraninnLogisticBlueprintSeeder::class);
    $this->token = JWTAuth::fromUser(User::factory()->create());
});

it('lists the five columns in navigation order', function () {
    $this->getJson('/api/public/traninn-logistic/menu')
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('data.0.title', 'Inicio')
        ->assertJsonPath('data.1.title', 'Nuestros servicios')
        ->assertJsonPath('data.2.title', 'Sobre nosotros')
        ->assertJsonPath('data.3.title', 'Contacto')
        ->assertJsonPath('data.4.title', 'Ubicaciones');
});

it('hangs the three services off their column', function () {
    $this->getJson('/api/public/traninn-logistic/pages/nuestros-servicios')
        ->assertOk()
        ->assertJsonCount(2, 'data.sections')
        ->assertJsonPath('data.sections.0.type', 'page_hero')
        ->assertJsonPath('data.sections.1.type', 'rich_text');

    $this->getJson('/api/public/traninn-logistic/menu')
        ->assertOk()
        ->assertJsonCount(3, 'data.1.children')
        ->assertJsonPath('data.1.children.0.title', 'Transporte de GNC')
        ->assertJsonPath('data.1.children.1.title', 'Logística')
        ->assertJsonPath('data.1.children.2.title', 'Carga pesada');
});

it('keeps the service pages informative, with no lead form', function () {
    foreach (['transporte-de-gnc', 'logistica', 'carga-pesada'] as $slug) {
        $response = $this->getJson("/api/public/traninn-logistic/pages/{$slug}")
            ->assertOk()
            ->assertJsonCount(3, 'data.sections')
            ->assertJsonPath('data.sections.0.type', 'page_hero')
            ->assertJsonPath('data.sections.1.type', 'rich_text')
            ->assertJsonPath('data.sections.2.type', 'feature_grid');

        expect(collect($response->json('data.sections'))->pluck('type'))
            ->not->toContain('lead_capture_form');
    }
});

it('returns the locations page with its map', function () {
    $this->getJson('/api/public/traninn-logistic/pages/ubicaciones')
        ->assertOk()
        ->assertJsonPath('data.path', '/ubicaciones')
        ->assertJsonCount(2, 'data.sections')
        ->assertJsonPath('data.sections.0.type', 'page_hero')
        ->assertJsonPath('data.sections.1.type', 'stations_map')
        ->assertJsonPath('data.sections.1.content.title', 'Nuestras bases');
});

/**
 * The pins are rows of `stations`, not fields of the section, and only the
 * active ones reach the public endpoint.
 */
it('feeds the map from the stations table, active ones only', function () {
    $this->getJson('/api/public/traninn-logistic/stations')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Base Morelia')
        ->assertJsonPath('data.1.name', 'Base Uruapan');

    $station = Station::where('name', 'Base Uruapan')->firstOrFail();

    $this->withToken($this->token)
        ->putJson("/api/admin/stations/{$station->id}", [
            'name' => 'Base Uruapan',
            'address' => $station->address,
            'latitude' => $station->latitude,
            'longitude' => $station->longitude,
            'phone' => $station->phone,
            'schedule' => $station->schedule,
            'is_active' => false,
        ])
        ->assertOk();

    $this->getJson('/api/public/traninn-logistic/stations')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('keeps the map center and zoom locked', function () {
    $section = traninnSection('stations_map', 'ubicaciones');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'title' => 'Dónde encontrarnos',
                'center' => '0,0',
                'zoom' => '20',
            ],
        ])
        ->assertOk();

    $content = traninnSection('stations_map', 'ubicaciones')->content;

    expect($content['title'])->toBe('Dónde encontrarnos')
        ->and($content['center'])->toBe('19.7028,-101.1920')
        ->and($content['zoom'])->toBe('11');
});
