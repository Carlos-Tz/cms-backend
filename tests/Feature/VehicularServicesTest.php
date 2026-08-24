<?php

use App\Models\Lead;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\GnuVehicularBlueprintSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GnuVehicularBlueprintSeeder::class);
    $this->token = JWTAuth::fromUser(User::factory()->create());
});

// `vehicularSection()` lives in VehicularHomeTest: Pest helpers are global.
// Every type of this column repeats across pages, so it always needs a slug.

it('returns each services page with its sections in blueprint order', function (string $slug, array $types) {
    $response = $this->getJson("/api/public/gnu-vehicular/pages/{$slug}")
        ->assertOk()
        ->assertJsonCount(count($types), 'data.sections');

    foreach ($types as $index => $type) {
        $response->assertJsonPath("data.sections.{$index}.type", $type);
    }
})->with([
    ['nuestros-servicios', ['page_hero', 'rich_text']],
    ['instala-tu-equipo-de-gnv', ['page_hero', 'rich_text', 'feature_grid']],
    ['cotiza-tu-equipo', ['page_hero', 'rich_text', 'lead_capture_form']],
    ['financiamiento', ['page_hero', 'rich_text', 'feature_grid']],
    ['flotillas-empresariales', ['page_hero', 'rich_text', 'feature_grid', 'lead_capture_form']],
    ['estaciones-moviles', ['page_hero', 'rich_text', 'feature_grid']],
]);

it('serves a third level page by its slug and full path', function () {
    $this->getJson('/api/public/gnu-vehicular/pages/cotiza-tu-equipo')
        ->assertOk()
        ->assertJsonPath('data.title', 'Cotiza tu equipo')
        ->assertJsonPath('data.path', '/nuestros-servicios/instala-tu-equipo-de-gnv/cotiza-tu-equipo');
});

it('nests three levels in the menu', function () {
    $this->getJson('/api/public/gnu-vehicular/menu')
        ->assertOk()
        ->assertJsonPath('data.1.title', 'Nuestros servicios')
        ->assertJsonCount(3, 'data.1.children')
        ->assertJsonPath('data.1.children.0.title', 'Instala tu equipo de GNV')
        ->assertJsonCount(2, 'data.1.children.0.children')
        ->assertJsonPath('data.1.children.0.children.0.title', 'Cotiza tu equipo')
        ->assertJsonPath('data.1.children.0.children.1.title', 'Financiamiento')
        ->assertJsonPath('data.1.children.1.title', 'Flotillas empresariales')
        ->assertJsonPath('data.1.children.2.title', 'Estaciones móviles');
});

it('drops the whole branch when a middle page is unpublished', function () {
    Site::where('slug', 'gnu-vehicular')->firstOrFail()
        ->pages()->where('slug', 'instala-tu-equipo-de-gnv')->update(['is_published' => false]);

    $this->getJson('/api/public/gnu-vehicular/menu')
        ->assertOk()
        // The two grandchildren go with their parent: nothing links to them.
        ->assertJsonCount(2, 'data.1.children')
        ->assertJsonMissing(['title' => 'Instala tu equipo de GNV'])
        ->assertJsonMissing(['title' => 'Cotiza tu equipo'])
        ->assertJsonMissing(['title' => 'Financiamiento']);

    $this->getJson('/api/public/gnu-vehicular/pages/instala-tu-equipo-de-gnv')->assertNotFound();
});

it('captures a lead from the equipment quote form', function () {
    $this->postJson('/api/public/gnu-vehicular/leads', [
        'name' => 'Luis Mora',
        'email' => 'luis@example.com',
        'phone' => '4432223333',
        'message' => 'Quiero cotizar la conversión de un Versa 2020.',
    ])->assertCreated();

    expect(Lead::firstOrFail()->site->slug)->toBe('gnu-vehicular');
});

it('lets the admin write the financing plans', function () {
    $section = vehicularSection('feature_grid', 'financiamiento');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'title' => 'Planes de financiamiento',
                'items' => [
                    ['title' => 'Contado', 'text' => '10% de descuento.'],
                    ['title' => '12 meses', 'text' => 'Sin intereses con tarjetas participantes.'],
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonCount(2, 'data.content.items');

    expect(vehicularSection('feature_grid', 'financiamiento')->content['title'])
        ->toBe('Planes de financiamiento');
});

it('keeps the fleet form fields locked', function () {
    $section = vehicularSection('lead_capture_form', 'flotillas-empresariales');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'title' => 'Habla con un asesor',
                'submit_label' => 'Enviar',
                'fields' => ['name', 'unidades'],
            ],
        ])
        ->assertOk();

    expect(vehicularSection('lead_capture_form', 'flotillas-empresariales')->content['fields'])
        ->toBe(['name', 'email', 'phone', 'message']);
});
