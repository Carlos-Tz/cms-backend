<?php

use App\Models\BlogPost;
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

it('lists the seeded articles newest first, without their body', function () {
    $this->getJson('/api/public/gnu/blog')
        ->assertOk()
        ->assertJsonCount(4, 'data')
        ->assertJsonPath('meta.total', 4)
        ->assertJsonPath('data.0.title', 'Gas natural vs otros combustibles')
        ->assertJsonMissingPath('data.0.body');
});

it('returns a single article with its body', function () {
    $this->getJson('/api/public/gnu/blog/beneficios-del-gas-natural')
        ->assertOk()
        ->assertJsonPath('data.title', 'Beneficios del gas natural')
        ->assertJsonPath('data.body', '<p>Contenido pendiente de redactar para «Beneficios del gas natural».</p>');
});

it('hides drafts, scheduled articles and other sites posts', function () {
    BlogPost::factory()->for($this->site)->draft()->create(['slug' => 'borrador']);
    BlogPost::factory()->for($this->site)->scheduled()->create(['slug' => 'programado']);

    $other = Site::create(['slug' => 'tectuus', 'name' => 'Tectuus']);
    BlogPost::factory()->for($other)->create(['slug' => 'de-otro-sitio']);

    $this->getJson('/api/public/gnu/blog')
        ->assertOk()
        ->assertJsonCount(4, 'data');

    $this->getJson('/api/public/gnu/blog/borrador')->assertNotFound();
    $this->getJson('/api/public/gnu/blog/programado')->assertNotFound();
    $this->getJson('/api/public/gnu/blog/de-otro-sitio')->assertNotFound();

    $this->getJson('/api/public/tectuus/blog')->assertOk()->assertJsonCount(1, 'data');
});

it('serves the blog page with its hero only', function () {
    $this->getJson('/api/public/gnu/pages/blog')
        ->assertOk()
        ->assertJsonCount(1, 'data.sections')
        ->assertJsonPath('data.sections.0.type', 'page_hero')
        ->assertJsonPath('data.sections.0.content.title', 'Blog');
});

it('blocks the admin blog api without a token', function () {
    $this->getJson('/api/admin/blog-posts')
        ->assertUnauthorized()
        ->assertJsonPath('meta.message', 'No autenticado');
});

it('lists drafts through the admin api', function () {
    BlogPost::factory()->for($this->site)->draft()->create(['slug' => 'borrador']);

    $this->withToken($this->token)->getJson('/api/admin/blog-posts?site=gnu')
        ->assertOk()
        ->assertJsonPath('meta.total', 5)
        // Newest published first, drafts last.
        ->assertJsonPath('data.0.slug', 'gas-natural-vs-otros-combustibles')
        ->assertJsonPath('data.4.slug', 'borrador');
});

it('creates an article deriving the slug from the title', function () {
    $this->withToken($this->token)
        ->postJson('/api/admin/blog-posts', [
            'site_id' => $this->site->id,
            'title' => 'Cómo instalar GNV en tu flotilla',
            'body' => '<p>Guía paso a paso.</p>',
            'published_at' => now()->toDateTimeString(),
        ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'como-instalar-gnv-en-tu-flotilla')
        ->assertJsonPath('meta.message', 'Artículo creado correctamente');

    $this->getJson('/api/public/gnu/blog')->assertJsonCount(5, 'data');
});

it('rejects a duplicated slug in the same site but allows it in another', function () {
    $payload = [
        'site_id' => $this->site->id,
        'title' => 'Beneficios del gas natural',
        'body' => '<p>Otro artículo.</p>',
    ];

    $this->withToken($this->token)->postJson('/api/admin/blog-posts', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'slug' => 'Ya existe un artículo con ese slug en este sitio.',
        ]);

    $other = Site::create(['slug' => 'gnu-vehicular', 'name' => 'GNU Vehicular']);

    $this->withToken($this->token)
        ->postJson('/api/admin/blog-posts', [...$payload, 'site_id' => $other->id])
        ->assertCreated();
});

it('keeps an unpublished article out of the public listing', function () {
    $this->withToken($this->token)
        ->postJson('/api/admin/blog-posts', [
            'site_id' => $this->site->id,
            'title' => 'Borrador sin fecha',
            'body' => '<p>Pendiente.</p>',
        ])
        ->assertCreated()
        ->assertJsonPath('data.published_at', null);

    $this->getJson('/api/public/gnu/blog')->assertJsonCount(4, 'data');
});

it('updates and deletes an article', function () {
    $post = BlogPost::factory()->for($this->site)->create(['slug' => 'un-articulo']);

    $this->withToken($this->token)
        ->putJson("/api/admin/blog-posts/{$post->id}", [
            'slug' => 'un-articulo',
            'title' => 'Título corregido',
            'body' => '<p>Contenido corregido.</p>',
            'published_at' => $post->published_at->toDateTimeString(),
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Título corregido');

    $this->withToken($this->token)
        ->deleteJson("/api/admin/blog-posts/{$post->id}")
        ->assertOk()
        ->assertJsonPath('meta.message', 'Artículo eliminado correctamente');

    expect(BlogPost::find($post->id))->toBeNull();
});

it('refuses to move an article to another site', function () {
    $post = BlogPost::factory()->for($this->site)->create(['slug' => 'un-articulo']);
    $other = Site::create(['slug' => 'tectuus', 'name' => 'Tectuus']);

    $this->withToken($this->token)
        ->putJson("/api/admin/blog-posts/{$post->id}", [
            'site_id' => $other->id,
            'slug' => 'un-articulo',
            'title' => $post->title,
            'body' => $post->body,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['site_id' => 'Un artículo no puede cambiar de sitio.']);
});

it('does not expose blog categories', function () {
    $this->withToken($this->token)->getJson('/api/admin/blog-categories')->assertNotFound();
});
