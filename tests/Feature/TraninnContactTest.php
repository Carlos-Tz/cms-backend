<?php

use App\Models\Lead;
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

$payload = [
    'name' => 'Juan Pérez',
    'email' => 'juan@example.com',
    'phone' => '4430000000',
    'message' => 'Necesito transporte de GNC.',
];

it('returns the contact page with its two sections in blueprint order', function () {
    $this->getJson('/api/public/traninn-logistic/pages/contacto')
        ->assertOk()
        ->assertJsonPath('data.title', 'Contacto')
        ->assertJsonPath('data.path', '/contacto')
        ->assertJsonCount(2, 'data.sections')
        ->assertJsonPath('data.sections.0.type', 'page_hero')
        ->assertJsonPath('data.sections.1.type', 'lead_capture_form');
});

/**
 * The map belongs to `/ubicaciones` in this site, unlike `gnu` and
 * `gnu-vehicular`, whose contact pages carry one.
 */
it('leaves the stations map out of the contact page', function () {
    $response = $this->getJson('/api/public/traninn-logistic/pages/contacto')->assertOk();

    expect(collect($response->json('data.sections'))->pluck('type'))
        ->not->toContain('stations_map');
});

it('captures a lead from the contact form', function () use ($payload) {
    $this->postJson('/api/public/traninn-logistic/leads', [...$payload, 'source' => 'contacto'])
        ->assertCreated();

    expect(Lead::sole()->source)->toBe('contacto');
});

/**
 * The site carries two forms — this one and the home page's — and `source` is
 * the only thing that tells them apart in the inbox.
 */
it('separates the two forms of the site in the admin inbox', function () use ($payload) {
    $this->postJson('/api/public/traninn-logistic/leads', [...$payload, 'source' => 'contacto'])
        ->assertCreated();

    $this->postJson('/api/public/traninn-logistic/leads', [
        ...$payload, 'name' => 'Desde la portada', 'source' => 'inicio',
    ])->assertCreated();

    $this->withToken($this->token)
        ->getJson('/api/admin/leads?site=traninn-logistic&source=contacto')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.name', 'Juan Pérez');

    $this->withToken($this->token)
        ->getJson('/api/admin/leads?site=traninn-logistic')
        ->assertOk()
        ->assertJsonPath('meta.total', 2);
});

it('rejects extra fields: the contact form declares none', function () use ($payload) {
    $this->postJson('/api/public/traninn-logistic/leads', [
        ...$payload,
        'source' => 'contacto',
        'details' => ['cargo_type' => 'GNC'],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'details' => 'El formulario de esta página no acepta: cargo_type.',
        ]);

    expect(Lead::count())->toBe(0);
});

it('keeps the contact form fields locked', function () {
    $section = traninnSection('lead_capture_form', 'contacto');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'title' => 'Contáctanos',
                'submit_label' => 'Enviar',
                'fields' => ['name', 'rfc'],
            ],
        ])
        ->assertOk();

    expect(traninnSection('lead_capture_form', 'contacto')->content['fields'])
        ->toBe(['name', 'email', 'phone', 'message']);
});
