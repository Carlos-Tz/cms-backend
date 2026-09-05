<?php

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

/**
 * «¿Quiénes somos?» is the content of the column head, not a child page: the
 * client hung a single node off the column and it names its own content.
 */
it('returns the about page with its three sections in blueprint order', function () {
    $this->getJson('/api/public/tectuus/pages/sobre-nosotros')
        ->assertOk()
        ->assertJsonPath('data.title', 'Sobre nosotros')
        ->assertJsonPath('data.path', '/sobre-nosotros')
        ->assertJsonCount(3, 'data.sections')
        ->assertJsonPath('data.sections.0.type', 'page_hero')
        ->assertJsonPath('data.sections.0.content.title', '¿Quiénes somos?')
        ->assertJsonPath('data.sections.1.type', 'rich_text')
        ->assertJsonPath('data.sections.2.type', 'image_gallery')
        ->assertJsonCount(2, 'data.sections.2.content.images');
});

it('lists sobre nosotros as the second column with no child pages', function () {
    $this->getJson('/api/public/tectuus/menu')
        ->assertOk()
        ->assertJsonPath('data.1.title', 'Sobre nosotros')
        ->assertJsonCount(0, 'data.1.children');
});

/**
 * `video_url` is where the «soporte para videos» requirement lands: this column
 * asked for images and video, and `rich_text` is the only type that carries one.
 */
it('stores the company video and copy through the admin API', function () {
    $section = tectuusSection('rich_text', 'sobre-nosotros');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'title' => 'Nuestra historia',
                'body' => '<p>Fundada en 2010 en Morelia.</p>',
                'video_url' => 'https://www.youtube.com/watch?v=abc123',
            ],
        ])
        ->assertOk();

    expect(tectuusSection('rich_text', 'sobre-nosotros')->content)
        ->toMatchArray([
            'title' => 'Nuestra historia',
            'video_url' => 'https://www.youtube.com/watch?v=abc123',
        ]);
});

it('replaces the gallery images through the admin API', function () {
    $section = tectuusSection('image_gallery', 'sobre-nosotros');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'title' => 'Nuestras instalaciones',
                'images' => [
                    ['image' => 'media/planta.jpg', 'caption' => 'Planta Morelia', 'alt' => 'Vista de la planta'],
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonCount(1, 'data.content.images');

    expect(tectuusSection('image_gallery', 'sobre-nosotros')->content['images'][0]['caption'])
        ->toBe('Planta Morelia');
});
