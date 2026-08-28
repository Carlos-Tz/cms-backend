<?php

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

it('lists the about column with its three child pages in the menu', function () {
    $this->getJson('/api/public/traninn-logistic/menu')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.title', 'Inicio')
        ->assertJsonPath('data.1.title', 'Sobre nosotros')
        ->assertJsonCount(3, 'data.1.children')
        ->assertJsonPath('data.1.children.0.title', '¿Qué hacemos?')
        ->assertJsonPath('data.1.children.1.title', 'Propósito empresarial')
        ->assertJsonPath('data.1.children.2.title', 'Nuestra historia');
});

/**
 * «Nuestros clientes» is a section of the column head, not a fourth child page:
 * the exception of the classification rule, since its name matches a type.
 */
it('carries the clients showcase as a section of the column head', function () {
    $this->getJson('/api/public/traninn-logistic/pages/sobre-nosotros')
        ->assertOk()
        ->assertJsonCount(3, 'data.sections')
        ->assertJsonPath('data.sections.0.type', 'page_hero')
        ->assertJsonPath('data.sections.1.type', 'rich_text')
        ->assertJsonPath('data.sections.2.type', 'clients_showcase')
        ->assertJsonCount(2, 'data.sections.2.content.logos')
        ->assertJsonPath('data.sections.2.content.logos.0.quote', 'Testimonio pendiente de redactar.');
});

it('returns the child pages with their sections in blueprint order', function () {
    $this->getJson('/api/public/traninn-logistic/pages/que-hacemos')
        ->assertOk()
        ->assertJsonPath('data.path', '/sobre-nosotros/que-hacemos')
        ->assertJsonCount(4, 'data.sections')
        ->assertJsonPath('data.sections.0.type', 'page_hero')
        ->assertJsonPath('data.sections.1.type', 'banner_carousel')
        ->assertJsonPath('data.sections.2.type', 'rich_text')
        ->assertJsonPath('data.sections.3.type', 'image_gallery');

    $this->getJson('/api/public/traninn-logistic/pages/proposito-empresarial')
        ->assertOk()
        ->assertJsonCount(3, 'data.sections')
        ->assertJsonPath('data.sections.2.type', 'image_gallery');

    $this->getJson('/api/public/traninn-logistic/pages/nuestra-historia')
        ->assertOk()
        ->assertJsonCount(4, 'data.sections')
        ->assertJsonPath('data.sections.1.type', 'banner_carousel')
        ->assertJsonPath('data.sections.3.type', 'image_gallery');
});

it('replaces the images of a gallery through the admin API', function () {
    $section = traninnSection('image_gallery', 'que-hacemos');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'title' => 'Nuestra flota',
                'images' => [
                    ['image' => 'media/flota-1.jpg', 'caption' => 'Unidad de GNC', 'alt' => 'Tracto con cisterna'],
                    ['image' => 'media/flota-2.jpg', 'caption' => null, 'alt' => null],
                    ['image' => 'media/flota-3.jpg', 'caption' => 'Patio de maniobras', 'alt' => null],
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonCount(3, 'data.content.images')
        ->assertJsonPath('data.content.images.0.caption', 'Unidad de GNC');

    expect(traninnSection('image_gallery', 'que-hacemos')->content['title'])->toBe('Nuestra flota');
});

it('rejects a gallery image without a file', function () {
    $section = traninnSection('image_gallery', 'nuestra-historia');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'title' => 'Línea del tiempo',
                'images' => [
                    ['caption' => 'Sin archivo', 'alt' => null],
                ],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('content.images.0.image');
});

it('stores a description and a testimonial per client without touching the home page one', function () {
    $section = traninnSection('clients_showcase', 'sobre-nosotros');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'title' => 'Nuestros clientes',
                'logos' => [
                    [
                        'image' => 'media/cliente-uno.png',
                        'name' => 'Cliente Uno',
                        'url' => 'https://cliente-uno.mx',
                        'description' => 'Transportamos su gas natural desde 2021.',
                        'quote' => 'Nunca hemos parado la planta por falta de suministro.',
                        'quote_author' => 'Jefe de operaciones',
                    ],
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.content.logos.0.quote_author', 'Jefe de operaciones');

    // The home page instance is a separate row: each one keeps its own values.
    expect(traninnSection('clients_showcase', 'inicio')->content['logos'][0]['name'])
        ->toBe('Cliente de ejemplo');
});
