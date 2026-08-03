<?php

use App\Models\Lead;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\GnuBlueprintSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GnuBlueprintSeeder::class);
    $this->site = Site::where('slug', 'gnu')->firstOrFail();
    $this->token = JWTAuth::fromUser(User::factory()->create());
});

$payload = [
    'name' => 'Juan Pérez',
    'email' => 'juan@example.com',
    'phone' => '8181234567',
    'message' => 'Quiero cotizar GNV para mi flotilla.',
];

it('captures a lead from the public form', function () use ($payload) {
    $this->postJson('/api/public/gnu/leads', $payload)
        ->assertCreated()
        ->assertJsonPath('data', null)
        ->assertJsonPath('meta.message', 'Gracias por contactarnos. Un asesor se comunicará contigo.');

    $lead = Lead::sole();

    expect($lead->site_id)->toBe($this->site->id)
        ->and($lead->name)->toBe('Juan Pérez')
        ->and($lead->message)->toBe('Quiero cotizar GNV para mi flotilla.');
});

it('accepts a lead without phone or message', function () {
    $this->postJson('/api/public/gnu/leads', [
        'name' => 'Ana López',
        'email' => 'ana@example.com',
    ])->assertCreated();

    expect(Lead::sole()->phone)->toBeNull();
});

it('rejects a lead without name or with an invalid email', function () {
    $this->postJson('/api/public/gnu/leads', ['email' => 'no-es-un-correo'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name' => 'Escribe tu nombre.',
            'email' => 'El correo electrónico no es válido.',
        ]);

    expect(Lead::count())->toBe(0);
});

it('ignores a site_id sent in the payload: the site comes from the url', function () use ($payload) {
    $other = Site::create(['slug' => 'tectuus', 'name' => 'Tectuus']);

    $this->postJson('/api/public/gnu/leads', [...$payload, 'site_id' => $other->id])
        ->assertCreated();

    expect(Lead::sole()->site_id)->toBe($this->site->id);
});

it('rejects a lead for an unknown site', function () use ($payload) {
    $this->postJson('/api/public/sitio-inexistente/leads', $payload)->assertNotFound();
});

it('blocks the admin lead api without a token', function () {
    $this->getJson('/api/admin/leads')
        ->assertUnauthorized()
        ->assertJsonPath('meta.message', 'No autenticado');
});

it('lists leads newest first and filters them by site', function () {
    $other = Site::create(['slug' => 'tectuus', 'name' => 'Tectuus']);

    Lead::factory()->for($this->site)->create(['name' => 'Antiguo', 'created_at' => now()->subDay()]);
    Lead::factory()->for($this->site)->create(['name' => 'Reciente']);
    Lead::factory()->for($other)->create(['name' => 'De otro sitio']);

    $this->withToken($this->token)->getJson('/api/admin/leads?site=gnu')
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0.name', 'Reciente')
        ->assertJsonPath('data.1.name', 'Antiguo');

    $this->withToken($this->token)->getJson('/api/admin/leads')
        ->assertOk()
        ->assertJsonPath('meta.total', 3);
});

it('shows a lead with its site and deletes it', function () {
    $lead = Lead::factory()->for($this->site)->create();

    $this->withToken($this->token)->getJson("/api/admin/leads/{$lead->id}")
        ->assertOk()
        ->assertJsonPath('data.email', $lead->email)
        ->assertJsonPath('data.site.slug', 'gnu');

    $this->withToken($this->token)->deleteJson("/api/admin/leads/{$lead->id}")
        ->assertOk()
        ->assertJsonPath('meta.message', 'Lead eliminado correctamente');

    expect(Lead::find($lead->id))->toBeNull();
});

it('does not let the admin create or edit a lead', function () use ($payload) {
    $lead = Lead::factory()->for($this->site)->create();

    $this->withToken($this->token)->postJson('/api/admin/leads', $payload)
        ->assertMethodNotAllowed();

    $this->withToken($this->token)->putJson("/api/admin/leads/{$lead->id}", $payload)
        ->assertMethodNotAllowed();
});

it('throttles the public form after 10 submissions per minute', function () use ($payload) {
    foreach (range(1, 10) as $i) {
        $this->postJson('/api/public/gnu/leads', $payload)->assertCreated();
    }

    $this->postJson('/api/public/gnu/leads', $payload)->assertTooManyRequests();
});
