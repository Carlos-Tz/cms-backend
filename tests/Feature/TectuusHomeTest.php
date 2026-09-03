<?php

use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\TectuusBlueprintSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(TectuusBlueprintSeeder::class);
    $this->token = JWTAuth::fromUser(User::factory()->create());
});

/**
 * Named apart from the helpers of the other site tests: Pest helpers are global.
 * No `$pageSlug` yet — this site has a single seeded page, so no type repeats.
 */
function tectuusSection(string $type): Section
{
    return Site::where('slug', 'tectuus')->firstOrFail()
        ->sections()
        ->where('type', $type)
        ->firstOrFail();
}

it('returns the home page with its four sections in blueprint order', function () {
    $this->getJson('/api/public/tectuus/pages/inicio')
        ->assertOk()
        ->assertJsonPath('data.title', 'Inicio')
        ->assertJsonPath('data.path', '/')
        ->assertJsonCount(4, 'data.sections')
        ->assertJsonPath('data.sections.0.type', 'banner_carousel')
        ->assertJsonPath('data.sections.1.type', 'rich_text')
        ->assertJsonPath('data.sections.2.type', 'clients_showcase')
        ->assertJsonPath('data.sections.2.content.title', 'Nuestros clientes')
        ->assertJsonPath('data.sections.3.type', 'lead_capture_form');
});

/**
 * The home page carries no `page_hero`: the banner carousel is its hero, same
 * as in the other three sites.
 */
it('keeps the home page free of a page hero', function () {
    $response = $this->getJson('/api/public/tectuus/pages/inicio')->assertOk();

    expect(collect($response->json('data.sections'))->pluck('type'))->not->toContain('page_hero');
});

it('returns the site-wide sections without testimonials', function () {
    $this->getJson('/api/public/tectuus/globals')
        ->assertOk()
        ->assertJsonPath('data.contact_info.email', 'contacto@tectuus.mx')
        ->assertJsonPath('data.social_icons.networks.0.network', 'facebook')
        ->assertJsonPath('data.whatsapp_button.phone', '+524430000000')
        ->assertJsonMissingPath('data.testimonials');
});

/**
 * This is the only site of the four without a savings simulator, so the
 * endpoint has no section to resolve.
 */
it('has no savings simulator', function () {
    $this->getJson('/api/public/tectuus/savings-simulator?fuel=diesel&quantity=1000')
        ->assertNotFound();
});

it('replaces the client logos through the admin API', function () {
    $section = tectuusSection('clients_showcase');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'title' => 'Empresas que confían en nosotros',
                'logos' => [
                    ['image' => 'media/cliente-uno.png', 'name' => 'Cliente Uno', 'url' => 'https://cliente-uno.mx'],
                    ['image' => 'media/cliente-dos.png', 'name' => 'Cliente Dos', 'url' => null],
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonCount(2, 'data.content.logos');

    expect(tectuusSection('clients_showcase')->content['title'])
        ->toBe('Empresas que confían en nosotros');
});

it('keeps the lead form fields locked', function () {
    $section = tectuusSection('lead_capture_form');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'title' => 'Escríbenos',
                'submit_label' => 'Enviar',
                'fields' => ['name', 'rfc'],
            ],
        ])
        ->assertOk();

    expect(tectuusSection('lead_capture_form')->content['fields'])
        ->toBe(['name', 'email', 'phone', 'message']);
});
