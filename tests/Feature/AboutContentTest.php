<?php

use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\GnuBlueprintSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GnuBlueprintSeeder::class);
    $this->token = JWTAuth::fromUser(User::factory()->create());
});

/**
 * Unlike HomeContentTest, these pages share types (five page_hero, three
 * feature_grid), so the lookup has to be scoped to a page.
 */
function sectionOfPage(string $pageSlug, string $type): Section
{
    return Site::where('slug', 'gnu')->firstOrFail()
        ->sections()
        ->where('type', $type)
        ->whereHas('page', fn ($page) => $page->where('slug', $pageSlug))
        ->firstOrFail();
}

it('returns the valores page with its sections in blueprint order', function () {
    $this->getJson('/api/public/gnu/pages/valores')
        ->assertOk()
        ->assertJsonPath('data.title', 'Valores')
        ->assertJsonPath('data.path', '/sobre-nosotros/valores')
        ->assertJsonPath('data.sections.0.type', 'page_hero')
        ->assertJsonPath('data.sections.1.type', 'feature_grid')
        ->assertJsonCount(4, 'data.sections.1.content.items');
});

it('returns the proposito empresarial page with its three sections', function () {
    $this->getJson('/api/public/gnu/pages/proposito-empresarial')
        ->assertOk()
        ->assertJsonPath('data.sections.0.type', 'page_hero')
        ->assertJsonPath('data.sections.1.type', 'rich_text')
        ->assertJsonPath('data.sections.2.type', 'feature_grid')
        ->assertJsonPath('data.sections.2.content.items.0.title', 'Misión');
});

it('replaces the feature grid items through the admin API', function () {
    $section = sectionOfPage('valores', 'feature_grid');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'title' => 'Nuestros valores',
                'items' => [
                    ['icon' => 'media/seguridad.svg', 'title' => 'Seguridad', 'text' => 'Primero, siempre.'],
                    ['title' => 'Innovación', 'text' => 'Buscamos mejores formas de hacerlo.'],
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonCount(2, 'data.content.items')
        ->assertJsonPath('data.content.items.1.title', 'Innovación');

    expect(sectionOfPage('valores', 'feature_grid')->content['items'])->toHaveCount(2);
});

it('rejects a feature grid item without a title', function () {
    $section = sectionOfPage('certificaciones', 'feature_grid');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => ['items' => [['text' => 'Sin título']]],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['content.items.0.title']);
});

it('updates a rich text section with a video', function () {
    $section = sectionOfPage('nuestra-historia', 'rich_text');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'title' => 'Desde 1998',
                'body' => '<p>Nuestra historia empieza en Monterrey.</p>',
                'video_url' => 'https://www.youtube.com/watch?v=abc123',
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.content.title', 'Desde 1998')
        ->assertJsonPath('data.content.video_url', 'https://www.youtube.com/watch?v=abc123');
});

it('rejects a rich text section with an invalid video url and no body', function () {
    $section = sectionOfPage('nuestra-historia', 'rich_text');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => ['video_url' => 'no-es-una-url'],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['content.body', 'content.video_url']);
});
