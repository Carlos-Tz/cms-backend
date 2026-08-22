<?php

use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\GnuBlueprintSeeder;
use Database\Seeders\GnuVehicularBlueprintSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GnuVehicularBlueprintSeeder::class);
    $this->token = JWTAuth::fromUser(User::factory()->create());
});

/**
 * `$pageSlug` is needed for the types the site repeats across pages —
 * `lead_capture_form` and `stations_map` live on both `/` and `/contacto`.
 * Named apart from `sectionOfType()` in HomeContentTest: Pest helpers are global.
 */
function vehicularSection(string $type, ?string $pageSlug = null): Section
{
    return Site::where('slug', 'gnu-vehicular')->firstOrFail()
        ->sections()
        ->where('type', $type)
        ->when(
            $pageSlug !== null,
            fn ($query) => $query->whereHas('page', fn ($page) => $page->where('slug', $pageSlug)),
        )
        ->firstOrFail();
}

it('returns the home page with its six sections in blueprint order', function () {
    $this->getJson('/api/public/gnu-vehicular/pages/inicio')
        ->assertOk()
        ->assertJsonPath('data.title', 'Inicio')
        ->assertJsonPath('data.path', '/')
        ->assertJsonCount(6, 'data.sections')
        ->assertJsonPath('data.sections.0.type', 'banner_carousel')
        ->assertJsonPath('data.sections.1.type', 'savings_simulator')
        ->assertJsonPath('data.sections.2.type', 'feature_grid')
        ->assertJsonPath('data.sections.2.content.title', 'Programa de recompensas')
        ->assertJsonPath('data.sections.3.type', 'testimonials')
        ->assertJsonPath('data.sections.4.type', 'stations_map')
        ->assertJsonPath('data.sections.5.type', 'lead_capture_form');
});

it('keeps testimonials on the home page instead of the site-wide sections', function () {
    $this->getJson('/api/public/gnu-vehicular/globals')
        ->assertOk()
        ->assertJsonPath('data.contact_info.email', 'contacto@gnuvehicular.mx')
        ->assertJsonPath('data.social_icons.networks.0.network', 'facebook')
        ->assertJsonPath('data.whatsapp_button.phone', '+524430000000')
        ->assertJsonMissingPath('data.testimonials');
});

it('lists the seeded nav columns in blueprint order', function () {
    $this->getJson('/api/public/gnu-vehicular/menu')
        ->assertOk()
        ->assertJsonCount(4, 'data')
        ->assertJsonPath('data.0.title', 'Inicio')
        ->assertJsonPath('data.1.title', 'Ubicaciones')
        ->assertJsonPath('data.2.title', 'Contacto')
        ->assertJsonPath('data.3.title', 'Blog');
});

it('simulates savings with this site prices, not the institutional ones', function () {
    $this->seed(GnuBlueprintSeeder::class);

    // 1000 L diésel x 26.40 = 26,400. Equivalent gas: 950 m3 x 13.20 = 12,540.
    $this->getJson('/api/public/gnu-vehicular/savings-simulator?fuel=diesel&quantity=1000')
        ->assertOk()
        ->assertJsonPath('data.current_fuel.cost', 26400)
        ->assertJsonPath('data.natural_gas.cost', 12540)
        ->assertJsonPath('data.savings', 13860)
        ->assertJsonPath('meta.site', 'gnu-vehicular');
});

it('returns the stations that feed the map', function () {
    $this->getJson('/api/public/gnu-vehicular/stations')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Estación Morelia');
});

it('edits the rewards program through the admin API', function () {
    $section = vehicularSection('feature_grid');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'title' => 'Recompensas GNV',
                'items' => [
                    ['title' => 'Acumula puntos', 'text' => 'Un punto por cada m³ cargado.'],
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonCount(1, 'data.content.items');

    expect(vehicularSection('feature_grid')->content['title'])->toBe('Recompensas GNV');
});

it('keeps the lead form fields locked', function () {
    $section = vehicularSection('lead_capture_form', 'inicio');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'title' => 'Escríbenos',
                'submit_label' => 'Enviar',
                'fields' => ['name', 'rfc'],
            ],
        ])
        ->assertOk();

    expect(vehicularSection('lead_capture_form')->content['fields'])
        ->toBe(['name', 'email', 'phone', 'message']);
});
