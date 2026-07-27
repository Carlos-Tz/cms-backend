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

function sectionOfType(string $type): Section
{
    return Site::where('slug', 'gnu')->firstOrFail()
        ->sections()->where('type', $type)->firstOrFail();
}

it('returns the home page with its sections in blueprint order', function () {
    $this->getJson('/api/public/gnu/pages/inicio')
        ->assertOk()
        ->assertJsonPath('data.title', 'Inicio')
        ->assertJsonPath('data.sections.0.type', 'banner_carousel')
        ->assertJsonPath('data.sections.1.type', 'savings_simulator')
        ->assertJsonPath('data.sections.2.type', 'clients_showcase')
        ->assertJsonPath('data.sections.3.type', 'lead_capture_form');
});

it('returns the site-wide sections keyed by type', function () {
    $this->getJson('/api/public/gnu/globals')
        ->assertOk()
        ->assertJsonPath('data.contact_info.email', 'contacto@gnu.mx')
        ->assertJsonPath('data.social_icons.networks.0.network', 'facebook')
        ->assertJsonPath('data.whatsapp_button.phone', '+528100000000');
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
