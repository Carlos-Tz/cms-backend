<?php

use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\TraninnLogisticBlueprintSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(TraninnLogisticBlueprintSeeder::class);
    $this->token = JWTAuth::fromUser(User::factory()->create());
});

/**
 * Named apart from the helpers of the other site tests: Pest helpers are global.
 * No page filter needed — the site seeds a single page today.
 */
function traninnSection(string $type): Section
{
    return Site::where('slug', 'traninn-logistic')->firstOrFail()
        ->sections()
        ->where('type', $type)
        ->firstOrFail();
}

it('returns the home page with its five sections in blueprint order', function () {
    $this->getJson('/api/public/traninn-logistic/pages/inicio')
        ->assertOk()
        ->assertJsonPath('data.title', 'Inicio')
        ->assertJsonPath('data.path', '/')
        ->assertJsonCount(5, 'data.sections')
        ->assertJsonPath('data.sections.0.type', 'banner_carousel')
        ->assertJsonPath('data.sections.1.type', 'savings_simulator')
        ->assertJsonPath('data.sections.2.type', 'rich_text')
        ->assertJsonPath('data.sections.3.type', 'clients_showcase')
        ->assertJsonPath('data.sections.3.content.title', 'Nuestros clientes')
        ->assertJsonPath('data.sections.4.type', 'lead_capture_form');
});

it('returns the site-wide sections without testimonials', function () {
    $this->getJson('/api/public/traninn-logistic/globals')
        ->assertOk()
        ->assertJsonPath('data.contact_info.email', 'contacto@traninnlogistic.mx')
        ->assertJsonPath('data.social_icons.networks.0.network', 'facebook')
        ->assertJsonPath('data.whatsapp_button.phone', '+524430000000')
        ->assertJsonMissingPath('data.testimonials');
});

it('simulates savings with this site prices', function () {
    // 1000 L diésel x 26.40 = 26,400. Equivalent gas: 950 m3 x 12.80 = 12,160.
    $this->getJson('/api/public/traninn-logistic/savings-simulator?fuel=diesel&quantity=1000')
        ->assertOk()
        ->assertJsonPath('data.current_fuel.cost', 26400)
        ->assertJsonPath('data.natural_gas.cost', 12160)
        ->assertJsonPath('data.savings', 14240)
        ->assertJsonPath('meta.site', 'traninn-logistic');
});

it('replaces the client logos through the admin API', function () {
    $section = traninnSection('clients_showcase');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'title' => 'Empresas que confían en nosotros',
                'logos' => [
                    ['image' => 'media/cliente-uno.png', 'name' => 'Cliente Uno', 'url' => 'https://cliente-uno.mx'],
                    ['image' => 'media/cliente-dos.png', 'name' => 'Cliente Dos', 'url' => null],
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonCount(2, 'data.content.logos');

    expect(traninnSection('clients_showcase')->content['title'])
        ->toBe('Empresas que confían en nosotros');
});

it('keeps the lead form fields locked', function () {
    $section = traninnSection('lead_capture_form');

    $this->withToken($this->token)
        ->putJson("/api/admin/sections/{$section->id}", [
            'content' => [
                'title' => 'Escríbenos',
                'submit_label' => 'Enviar',
                'fields' => ['name', 'rfc'],
            ],
        ])
        ->assertOk();

    expect(traninnSection('lead_capture_form')->content['fields'])
        ->toBe(['name', 'email', 'phone', 'message']);
});
