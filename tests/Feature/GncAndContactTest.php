<?php

use App\Models\Site;
use Database\Seeders\GnuBlueprintSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GnuBlueprintSeeder::class);
});

it('serves GNC with the simulator and the map together', function () {
    // The blueprint's commercial priority: the only page carrying both.
    $this->getJson('/api/public/gnu/pages/gnc')
        ->assertOk()
        ->assertJsonPath('data.path', '/nuestros-servicios/gnc')
        ->assertJsonPath('data.sections.0.type', 'page_hero')
        ->assertJsonPath('data.sections.1.type', 'rich_text')
        ->assertJsonPath('data.sections.2.type', 'feature_grid')
        ->assertJsonPath('data.sections.3.type', 'savings_simulator')
        ->assertJsonPath('data.sections.4.type', 'stations_map')
        ->assertJsonPath('data.sections.5.type', 'lead_capture_form');
});

it('serves the contacto page with its form and map', function () {
    $this->getJson('/api/public/gnu/pages/contacto')
        ->assertOk()
        ->assertJsonPath('data.sections.0.type', 'page_hero')
        ->assertJsonPath('data.sections.1.type', 'lead_capture_form')
        ->assertJsonPath('data.sections.2.type', 'stations_map');
});

it('completes the 17 pages of the gnu blueprint', function () {
    expect(Site::where('slug', 'gnu')->firstOrFail()->pages()->count())->toBe(17);
});

it('orders the servicios children GNV, GNC, GND', function () {
    $menu = collect($this->getJson('/api/public/gnu/menu')->assertOk()->json('data'))
        ->firstWhere('slug', 'nuestros-servicios');

    expect(collect($menu['children'])->pluck('slug')->all())->toBe(['gnv', 'gnc', 'gnd']);
});

it('picks the simulator of a given page', function () {
    $site = Site::where('slug', 'gnu')->firstOrFail();
    $section = $site->sections()
        ->where('type', 'savings_simulator')
        ->whereHas('page', fn ($page) => $page->where('slug', 'gnc'))
        ->firstOrFail();

    $content = $section->content;
    $content['fuel_prices'][0]['price_per_unit'] = 10;
    $section->update(['content' => $content]);

    // 1000 L diésel: gas equivalente 950 m³. A 10 el m³ en GNC, a 12.50 en portada.
    $this->getJson('/api/public/gnu/savings-simulator?fuel=diesel&quantity=1000&page=gnc')
        ->assertOk()
        ->assertJsonPath('data.natural_gas.cost', 9500);

    $this->getJson('/api/public/gnu/savings-simulator?fuel=diesel&quantity=1000')
        ->assertOk()
        ->assertJsonPath('data.natural_gas.cost', 11875);
});

it('404s on a page without a simulator', function () {
    $this->getJson('/api/public/gnu/savings-simulator?fuel=diesel&quantity=1000&page=valores')
        ->assertNotFound();
});
