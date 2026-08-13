<?php

use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\GnuBlueprintSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GnuBlueprintSeeder::class);
    // fromUser() instead of login(): login() would also authenticate the guard for
    // the rest of the test, hiding the unauthenticated cases.
    $this->token = JWTAuth::fromUser(User::factory()->create());
});

/**
 * `$pageSlug` is only needed for types the site repeats — `rich_text` has 12
 * instances, so looking it up by type alone would hit an arbitrary one.
 */
function sectionOfType(string $type, ?string $pageSlug = null): Section
{
    return Site::where('slug', 'gnu')->firstOrFail()
        ->sections()
        ->where('type', $type)
        ->when(
            $pageSlug !== null,
            fn ($query) => $query->whereHas('page', fn ($page) => $page->where('slug', $pageSlug)),
        )
        ->firstOrFail();
}

it('returns the home page with its sections in blueprint order', function () {
    $this->getJson('/api/public/gnu/pages/inicio')
        ->assertOk()
        ->assertJsonPath('data.title', 'Inicio')
        ->assertJsonCount(5, 'data.sections')
        ->assertJsonPath('data.sections.0.type', 'banner_carousel')
        ->assertJsonPath('data.sections.1.type', 'savings_simulator')
        ->assertJsonPath('data.sections.2.type', 'rich_text')
        ->assertJsonPath('data.sections.3.type', 'clients_showcase')
        ->assertJsonPath('data.sections.4.type', 'lead_capture_form');
});

it('returns the site-wide sections keyed by type', function () {
    $this->getJson('/api/public/gnu/globals')
        ->assertOk()
        ->assertJsonPath('data.contact_info.email', 'contacto@gnu.mx')
        ->assertJsonPath('data.social_icons.networks.0.network', 'facebook')
        ->assertJsonPath('data.whatsapp_button.phone', '+528100000000')
        ->assertJsonPath('data.testimonials.items.0.author', 'Cliente de ejemplo');
});

it('stores a video url on the home page rich text', function () {
    $section = sectionOfType('rich_text', 'inicio');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'body' => '<p>Conoce nuestra operación.</p>',
                'video_url' => 'https://www.youtube.com/watch?v=abc123',
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.content.video_url', 'https://www.youtube.com/watch?v=abc123');
});

it('replaces the testimonials through the admin API', function () {
    $section = sectionOfType('testimonials');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'title' => 'Testimonios',
                'items' => [
                    ['quote' => 'Bajamos el costo de combustible.', 'author' => 'Ana Ruiz', 'role' => 'Directora'],
                    ['quote' => 'Excelente servicio.', 'author' => 'Luis Mora'],
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonCount(2, 'data.content.items')
        ->assertJsonPath('data.content.items.0.role', 'Directora');

    expect(sectionOfType('testimonials')->content['items'])->toHaveCount(2);
});

it('rejects a testimonial without an author', function () {
    $section = sectionOfType('testimonials');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => ['items' => [['quote' => 'Sin autor']]],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['content.items.0.author']);
});

it('404s on an unknown site or page, in Spanish', function () {
    $this->getJson('/api/public/otro-sitio/pages/inicio')
        ->assertNotFound()
        ->assertJsonPath('meta.message', 'Recurso no encontrado');

    $this->getJson('/api/public/gnu/pages/no-existe')->assertNotFound();
});

it('hides an unpublished page from the public API', function () {
    Site::where('slug', 'gnu')->firstOrFail()
        ->pages()->where('slug', 'inicio')->update(['is_published' => false]);

    $this->getJson('/api/public/gnu/pages/inicio')->assertNotFound();
});

it('replaces the carousel slides through the admin API', function () {
    $section = sectionOfType('banner_carousel');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'slides' => [
                    ['image' => 'media/uno.jpg', 'title' => 'Uno'],
                    ['image' => 'media/dos.jpg', 'title' => 'Dos', 'link_url' => 'https://gnu.mx'],
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonCount(2, 'data.content.slides')
        ->assertJsonPath('data.content.slides.1.title', 'Dos');

    expect(sectionOfType('banner_carousel')->content['slides'])->toHaveCount(2);
});

it('rejects a slide without an image', function () {
    $section = sectionOfType('banner_carousel');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => ['slides' => [['title' => 'Sin imagen']]],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'content.slides.0.image' => 'El campo content.slides.0.image es obligatorio.',
        ]);
});

it('keeps locked fields when the admin updates a section', function () {
    $section = sectionOfType('lead_capture_form');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'title' => 'Escríbenos',
                'submit_label' => 'Enviar',
                'fields' => ['name', 'curp'],
            ],
        ])
        ->assertOk();

    expect(sectionOfType('lead_capture_form')->content['fields'])
        ->toBe(['name', 'email', 'phone', 'message']);
});

it('blocks section writes without a token', function () {
    $section = sectionOfType('contact_info');

    $this->putJson("/api/admin/sections/{$section->id}", ['content' => []])
        ->assertUnauthorized()
        ->assertJsonPath('meta.message', 'No autenticado');

    // Without an Accept header either: there is no `login` route to redirect to.
    $this->put("/api/admin/sections/{$section->id}")->assertUnauthorized();
});

it('does not expose routes to create or delete sections', function () {
    $section = sectionOfType('contact_info');

    $this->withToken($this->token)->postJson('/api/admin/sections', [])->assertMethodNotAllowed();
    $this->withToken($this->token)
        ->deleteJson("/api/admin/sections/{$section->id}")
        ->assertMethodNotAllowed();
});

it('computes the savings against diesel', function () {
    // 1000 L diésel × 25.90 = 25,900. Equivalent gas: 1000 × 0.95 = 950 m³ × 12.50 = 11,875.
    $this->getJson('/api/public/gnu/savings-simulator?fuel=diesel&quantity=1000')
        ->assertOk()
        ->assertJsonPath('data.current_fuel.cost', 25900)
        ->assertJsonPath('data.natural_gas.quantity', 950)
        ->assertJsonPath('data.natural_gas.cost', 11875)
        ->assertJsonPath('data.savings', 14025)
        ->assertJsonPath('data.savings_percent', 54.15);
});

it('reflects an updated gas price in the simulation', function () {
    $section = sectionOfType('savings_simulator');
    $content = $section->content;
    $content['fuel_prices'][0]['price_per_unit'] = 20;

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", ['content' => $content])
        ->assertOk();

    $this->getJson('/api/public/gnu/savings-simulator?fuel=diesel&quantity=1000')
        ->assertOk()
        ->assertJsonPath('data.natural_gas.cost', 19000);
});

it('rejects an unknown fuel and a non positive quantity', function () {
    $this->getJson('/api/public/gnu/savings-simulator?fuel=carbon&quantity=10')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['fuel']);

    $this->getJson('/api/public/gnu/savings-simulator?fuel=diesel&quantity=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['quantity']);
});

it('uploads an image and returns its url', function () {
    Storage::fake('public');

    $response = $this->withToken($this->token)
        ->postJson('/api/admin/media', ['file' => UploadedFile::fake()->image('banner.jpg')])
        ->assertCreated();

    Storage::disk('public')->assertExists($response->json('data.path'));
});

it('lists the uploaded images and deletes one', function () {
    Storage::fake('public');

    $path = $this->withToken($this->token)
        ->postJson('/api/admin/media', ['file' => UploadedFile::fake()->image('banner.jpg')])
        ->assertCreated()
        ->json('data.path');

    $name = basename($path);

    $this->withToken($this->token)
        ->getJson('/api/admin/media')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.name', $name);

    $this->withToken($this->token)
        ->deleteJson("/api/admin/media/{$name}")
        ->assertOk()
        ->assertJsonPath('meta.message', 'Archivo eliminado correctamente');

    Storage::disk('public')->assertMissing($path);
});

it('404s when deleting an image that does not exist', function () {
    Storage::fake('public');

    $this->withToken($this->token)
        ->deleteJson('/api/admin/media/no-existe.jpg')
        ->assertNotFound()
        ->assertJsonPath('meta.message', 'Recurso no encontrado');
});

it('cannot reach the media delete with a path outside the media folder', function () {
    Storage::fake('public');

    Storage::disk('public')->put('secreto.txt', 'no borrar');

    // The route param matches a single segment, so a traversal is rejected by the
    // router and never reaches the controller. Verified: the request 404s without
    // running the action. `basename()` there is the second line of defence, for
    // the day the route constraint changes.
    $this->withToken($this->token)
        ->deleteJson('/api/admin/media/..%2Fsecreto.txt')
        ->assertNotFound();

    Storage::disk('public')->assertExists('secreto.txt');
});

it('does not expose routes to show or update a media file', function () {
    $this->withToken($this->token)->getJson('/api/admin/media/banner.jpg')->assertMethodNotAllowed();
    $this->withToken($this->token)->putJson('/api/admin/media/banner.jpg')->assertMethodNotAllowed();
});

it('blocks the media api without a token', function () {
    $this->getJson('/api/admin/media')->assertUnauthorized();
    $this->deleteJson('/api/admin/media/banner.jpg')->assertUnauthorized();
});
