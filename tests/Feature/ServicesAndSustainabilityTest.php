<?php

use App\Models\Site;
use Database\Seeders\GnuBlueprintSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GnuBlueprintSeeder::class);
});

it('serves the nuestros servicios page with its three sections', function () {
    $this->getJson('/api/public/gnu/pages/nuestros-servicios')
        ->assertOk()
        ->assertJsonPath('data.path', '/nuestros-servicios')
        ->assertJsonPath('data.sections.0.type', 'page_hero')
        ->assertJsonPath('data.sections.1.type', 'rich_text')
        ->assertJsonPath('data.sections.2.type', 'clients_showcase');
});

it('serves a product page with its benefits grid and lead form', function (string $slug, string $title) {
    $this->getJson("/api/public/gnu/pages/{$slug}")
        ->assertOk()
        ->assertJsonPath('data.title', $title)
        ->assertJsonPath('data.path', "/nuestros-servicios/{$slug}")
        ->assertJsonPath('data.sections.2.type', 'feature_grid')
        ->assertJsonCount(3, 'data.sections.2.content.items')
        ->assertJsonPath('data.sections.3.type', 'lead_capture_form')
        // Locked field: it mirrors the columns of the `leads` table.
        ->assertJsonPath('data.sections.3.content.fields', ['name', 'email', 'phone', 'message']);
})->with([
    ['gnd', 'GND'],
    ['gnv', 'GNV'],
]);

it('serves the four text pages of desarrollo sostenible', function (string $slug) {
    $this->getJson("/api/public/gnu/pages/{$slug}")
        ->assertOk()
        ->assertJsonPath('data.sections.0.type', 'page_hero')
        ->assertJsonPath('data.sections.1.type', 'rich_text')
        ->assertJsonCount(2, 'data.sections');
})->with(['desarrollo-sostenible', 'distintivo-esr', 'ecovadis', 'voluntariado']);

it('serves iniciativas de RSE with a feature grid instead of rich text', function () {
    $this->getJson('/api/public/gnu/pages/iniciativas-rse')
        ->assertOk()
        ->assertJsonPath('data.sections.1.type', 'feature_grid')
        ->assertJsonCount(2, 'data.sections.1.content.items');
});

it('nests the children of both columns in the public menu', function () {
    $menu = collect($this->getJson('/api/public/gnu/menu')->assertOk()->json('data'))
        ->keyBy('slug');

    expect(collect($menu['desarrollo-sostenible']['children'])->pluck('slug')->all())
        ->toBe(['distintivo-esr', 'ecovadis', 'voluntariado', 'iniciativas-rse']);

    // GNC is asserted in GncAndContactTest, which owns that page.
    expect(collect($menu['nuestros-servicios']['children'])->pluck('slug')->all())
        ->toContain('gnd', 'gnv');
});

it('seeds every page of both columns', function () {
    $pages = Site::where('slug', 'gnu')->firstOrFail()->pages()->pluck('path');

    expect($pages)->toContain(
        '/nuestros-servicios',
        '/nuestros-servicios/gnd',
        '/nuestros-servicios/gnv',
        '/desarrollo-sostenible',
        '/desarrollo-sostenible/distintivo-esr',
        '/desarrollo-sostenible/ecovadis',
        '/desarrollo-sostenible/voluntariado',
        '/desarrollo-sostenible/iniciativas-rse',
    );
});
