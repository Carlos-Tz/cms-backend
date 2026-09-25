<?php

use App\Models\Lead;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\GnuBlueprintSeeder;
use Database\Seeders\GnuVehicularBlueprintSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GnuVehicularBlueprintSeeder::class);
    $this->token = JWTAuth::fromUser(User::factory()->create());
});

// `vehicularSection()` lives in VehicularHomeTest: Pest helpers are global.

it('returns the contact page with its four sections in blueprint order', function () {
    $this->getJson('/api/public/gnu-vehicular/pages/contacto')
        ->assertOk()
        ->assertJsonPath('data.title', 'Contacto')
        ->assertJsonCount(4, 'data.sections')
        ->assertJsonPath('data.sections.0.type', 'page_hero')
        ->assertJsonPath('data.sections.1.type', 'lead_capture_form')
        ->assertJsonPath('data.sections.1.content.title', 'Captación de leads')
        ->assertJsonPath('data.sections.2.type', 'rich_text')
        ->assertJsonPath('data.sections.2.content.title', 'Facturación')
        ->assertJsonPath('data.sections.3.type', 'stations_map');
});

it('captures a lead from the contact form', function () {
    $this->postJson('/api/public/gnu-vehicular/leads', [
        'name' => 'Ana Ruiz',
        'email' => 'ana@example.com',
        'phone' => '+52 443 111 2222',
        'message' => 'Quiero convertir mi flotilla a GNV.',
    ])
        ->assertCreated()
        ->assertJsonPath('data', null)
        ->assertJsonPath('meta.message', 'Gracias por contactarnos. Un asesor se comunicará contigo.');

    $lead = Lead::firstOrFail();

    expect($lead->email)->toBe('ana@example.com')
        ->and($lead->site_id)->toBe(Site::where('slug', 'gnu-vehicular')->firstOrFail()->id);
});

it('rejects a lead without a name or with an invalid email, in Spanish', function () {
    $this->postJson('/api/public/gnu-vehicular/leads', ['email' => 'no-es-correo'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name' => 'Escribe tu nombre.',
            'email' => 'El correo electrónico no es válido.',
        ]);

    expect(Lead::count())->toBe(0);
});

it('ignores a site_id sent by the visitor', function () {
    $gnu = Site::updateOrCreate(['slug' => 'gnu'], ['name' => 'GNU Gas Natural']);

    $this->postJson('/api/public/gnu-vehicular/leads', [
        'name' => 'Ana Ruiz',
        'email' => 'ana@example.com',
        'site_id' => $gnu->id,
    ])->assertCreated();

    expect(Lead::firstOrFail()->site->slug)->toBe('gnu-vehicular');
});

it('lists the lead in the admin inbox of its own site only', function () {
    $this->seed(GnuBlueprintSeeder::class);

    $this->postJson('/api/public/gnu-vehicular/leads', [
        'name' => 'Ana Ruiz',
        'email' => 'ana@example.com',
    ])->assertCreated();

    $this->withToken($this->token)
        ->getJson('/api/admin/leads?site=gnu-vehicular')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.email', 'ana@example.com');

    $this->withToken($this->token)
        ->getJson('/api/admin/leads?site=gnu')
        ->assertOk()
        ->assertJsonPath('meta.total', 0);
});

it('moves the map when the admin edits its center and zoom', function () {
    $section = vehicularSection('stations_map', 'contacto');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => ['title' => 'Encuéntranos', 'center' => '0,0', 'zoom' => '20'],
        ])
        ->assertOk()
        ->assertJsonPath('data.content.title', 'Encuéntranos');

    $content = vehicularSection('stations_map', 'contacto')->content;

    expect($content['center'])->toBe('0,0')
        ->and($content['zoom'])->toBe('20');
});

it('draws a station created by the admin, and hides an inactive one', function () {
    $site = Site::where('slug', 'gnu-vehicular')->firstOrFail();

    $this->withToken($this->token)
        ->postJson('/api/admin/stations', [
            'site_id' => $site->id,
            'name' => 'Estación Zamora',
            'address' => 'Carretera Zamora-La Barca km 3',
            'latitude' => 19.9853,
            'longitude' => -102.2836,
            'is_active' => true,
        ])
        ->assertCreated();

    $this->getJson('/api/public/gnu-vehicular/stations')
        ->assertOk()
        ->assertJsonPath('meta.total', 3);

    $site->stations()->where('name', 'Estación Zamora')->update(['is_active' => false]);

    $this->getJson('/api/public/gnu-vehicular/stations')
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonMissing(['name' => 'Estación Zamora']);

    // The admin inbox still sees it: only the public API filters by is_active.
    $this->withToken($this->token)
        ->getJson('/api/admin/stations?site=gnu-vehicular')
        ->assertOk()
        ->assertJsonPath('meta.total', 3);
});
