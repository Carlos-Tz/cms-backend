<?php

use App\Models\Page;
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

function gnuPage(string $slug): Page
{
    return Site::where('slug', 'gnu')->firstOrFail()
        ->pages()->where('slug', $slug)->firstOrFail();
}

it('derives the public menu from the page map', function () {
    $response = $this->getJson('/api/public/gnu/menu')->assertOk();

    // Root pages ordered by position: Inicio (1), Sobre nosotros (3), Blog (6).
    expect(collect($response->json('data'))->pluck('title')->all())
        ->toBe(['Inicio', 'Sobre nosotros', 'Blog']);

    $response
        ->assertJsonPath('data.1.path', '/sobre-nosotros')
        ->assertJsonCount(4, 'data.1.children')
        ->assertJsonPath('data.1.children.0.title', 'Propósito empresarial')
        ->assertJsonPath('data.1.children.3.slug', 'nuestra-historia')
        ->assertJsonCount(0, 'data.0.children');
});

it('drops an unpublished page and its whole branch from the menu', function () {
    gnuPage('sobre-nosotros')->update(['is_published' => false]);

    $titles = collect($this->getJson('/api/public/gnu/menu')->assertOk()->json('data'))
        ->pluck('title')
        ->all();

    // The children have no reachable route, so they must not surface as roots.
    expect($titles)->toBe(['Inicio', 'Blog']);
});

it('lists the pages of a site for the admin, including unpublished ones', function () {
    gnuPage('valores')->update(['is_published' => false]);

    $response = $this->withToken($this->token)
        ->getJson('/api/admin/pages?site=gnu')
        ->assertOk()
        ->assertJsonPath('meta.total', 7);

    $valores = collect($response->json('data'))->firstWhere('slug', 'valores');

    expect($valores['is_published'])->toBeFalse()
        ->and($valores['sections_count'])->toBe(2)
        ->and($valores['path'])->toBe('/sobre-nosotros/valores');
});

it('returns a page with its sections for the admin', function () {
    $page = gnuPage('proposito-empresarial');

    $this->withToken($this->token)
        ->getJson("/api/admin/pages/{$page->id}")
        ->assertOk()
        ->assertJsonPath('data.title', 'Propósito empresarial')
        ->assertJsonCount(3, 'data.sections')
        ->assertJsonPath('data.sections.2.type', 'feature_grid');
});

it('unpublishes and republishes a page through the admin API', function () {
    $page = gnuPage('certificaciones');

    $this->withToken($this->token)
        ->putJson("/api/admin/pages/{$page->id}", ['is_published' => false])
        ->assertOk()
        ->assertJsonPath('data.is_published', false)
        ->assertJsonPath('meta.message', 'Página despublicada correctamente');

    $this->getJson('/api/public/gnu/pages/certificaciones')->assertNotFound();

    $this->withToken($this->token)
        ->putJson("/api/admin/pages/{$page->id}", ['is_published' => true])
        ->assertOk()
        ->assertJsonPath('meta.message', 'Página publicada correctamente');

    $this->getJson('/api/public/gnu/pages/certificaciones')->assertOk();
});

it('ignores blueprint fields sent alongside the publish flag', function () {
    $page = gnuPage('valores');

    $this->withToken($this->token)
        ->putJson("/api/admin/pages/{$page->id}", [
            'is_published' => true,
            'title' => 'Otro título',
            'path' => '/hackeado',
            'parent_id' => null,
        ])
        ->assertOk();

    expect(gnuPage('valores')->only(['title', 'path', 'parent_id']))
        ->toBe($page->only(['title', 'path', 'parent_id']));
});

it('does not expose routes to create or delete pages', function () {
    $page = gnuPage('valores');

    $this->withToken($this->token)->postJson('/api/admin/pages', [])->assertMethodNotAllowed();
    $this->withToken($this->token)
        ->deleteJson("/api/admin/pages/{$page->id}")
        ->assertMethodNotAllowed();
});

it('blocks page writes without a token', function () {
    $page = gnuPage('valores');

    $this->putJson("/api/admin/pages/{$page->id}", ['is_published' => false])
        ->assertUnauthorized()
        ->assertJsonPath('meta.message', 'No autenticado');
});
