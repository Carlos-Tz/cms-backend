<?php

use App\Models\Lead;
use App\Models\User;
use Database\Seeders\TectuusBlueprintSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

// `tectuusSection()` lives in TectuusHomeTest: Pest helpers are global.

beforeEach(function () {
    $this->seed(TectuusBlueprintSeeder::class);
    $this->token = JWTAuth::fromUser(User::factory()->create());
});

$payload = [
    'name' => 'Juan Pérez',
    'email' => 'juan@example.com',
    'phone' => '4430000000',
    'message' => 'Necesito una cotización de compresoras.',
];

it('returns the contact page with its two sections in blueprint order', function () {
    $this->getJson('/api/public/tectuus/pages/contacto')
        ->assertOk()
        ->assertJsonPath('data.title', 'Contacto')
        ->assertJsonPath('data.path', '/contacto')
        ->assertJsonCount(2, 'data.sections')
        ->assertJsonPath('data.sections.0.type', 'page_hero')
        ->assertJsonPath('data.sections.1.type', 'lead_capture_form');
});

/**
 * The site has no locations and no map, so the contact page carries neither —
 * unlike `gnu` and `gnu-vehicular`.
 */
it('leaves the stations map out of the contact page', function () {
    $response = $this->getJson('/api/public/tectuus/pages/contacto')->assertOk();

    expect(collect($response->json('data.sections'))->pluck('type'))->not->toContain('stations_map');
});

it('captures a lead from the contact form', function () use ($payload) {
    $this->postJson('/api/public/tectuus/leads', [...$payload, 'source' => 'contacto'])
        ->assertCreated()
        ->assertJsonPath('status', 'success');

    expect(Lead::where('source', 'contacto')->first())
        ->not->toBeNull()
        ->email->toBe('juan@example.com');
});

/**
 * This is the site's second form — the other one is on the home page — so
 * `source` is what separates them in the inbox.
 */
it('separates the two forms of the site in the admin inbox', function () use ($payload) {
    $this->postJson('/api/public/tectuus/leads', [...$payload, 'source' => 'contacto'])->assertCreated();
    $this->postJson('/api/public/tectuus/leads', [...$payload, 'source' => 'inicio'])->assertCreated();

    $this->withToken($this->token)
        ->getJson('/api/admin/leads?site=tectuus&source=contacto')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.source', 'contacto');
});

/**
 * The form declares no extras, so `details` accepts nothing: `fields[]` is the
 * contract and `StoreLeadRequest` enforces it.
 */
it('rejects a field the contact form does not declare', function () use ($payload) {
    $this->postJson('/api/public/tectuus/leads', [
        ...$payload,
        'source' => 'contacto',
        'details' => ['rfc' => 'XAXX010101000'],
    ])->assertJsonValidationErrors('details');
});
