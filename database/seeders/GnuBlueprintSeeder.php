<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Site;
use Illuminate\Database\Seeder;

/**
 * Seeds the structure of the GNU home page plus the site-wide sections.
 *
 * Structure (pages, section types and their order) comes from the blueprint in
 * `.claude/skills/sitios-web-gnu`; the values below are placeholders the admin
 * replaces through the admin API. Idempotent: safe to re-run.
 */
class GnuBlueprintSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $site = Site::updateOrCreate(
            ['slug' => 'gnu'],
            ['name' => 'GNU Gas Natural'],
        );

        $home = Page::updateOrCreate(
            ['site_id' => $site->id, 'slug' => 'inicio'],
            ['path' => '/', 'title' => 'Inicio', 'position' => 1, 'is_published' => true],
        );

        // The home page carries no page_hero: the banner carousel is its hero.
        $this->section($site, $home, 'banner_carousel', 1, [
            'slides' => [
                [
                    'image' => 'media/placeholder-banner.jpg',
                    'title' => 'Energía que impulsa a México',
                    'subtitle' => 'Gas natural comprimido, distribuido y vehicular.',
                    'link_url' => null,
                    'link_label' => null,
                ],
            ],
        ]);

        $this->section($site, $home, 'savings_simulator', 2, [
            'title' => 'Simulador de ahorro',
            'intro' => 'Calcula cuánto ahorras al cambiar tu combustible actual por gas natural.',
            // `equivalence_factor` = units of natural gas equivalent to one unit of
            // this fuel. Natural gas is the baseline, so its own factor is 1.
            'fuel_prices' => [
                [
                    'fuel' => 'gas-natural',
                    'label' => 'Gas natural',
                    'price_per_unit' => 12.50,
                    'unit' => 'm³',
                    'equivalence_factor' => 1,
                ],
                [
                    'fuel' => 'diesel',
                    'label' => 'Diésel',
                    'price_per_unit' => 25.90,
                    'unit' => 'litro',
                    'equivalence_factor' => 0.95,
                ],
                [
                    'fuel' => 'gasolina',
                    'label' => 'Gasolina',
                    'price_per_unit' => 24.30,
                    'unit' => 'litro',
                    'equivalence_factor' => 0.84,
                ],
            ],
        ]);

        $this->section($site, $home, 'clients_showcase', 3, [
            'title' => 'Nuestros clientes',
            'logos' => [
                [
                    'image' => 'media/placeholder-logo.png',
                    'name' => 'Cliente de ejemplo',
                    'url' => null,
                ],
            ],
        ]);

        $this->section($site, $home, 'lead_capture_form', 4, [
            'title' => 'Contáctanos',
            'intro' => 'Déjanos tus datos y un asesor se comunicará contigo.',
            'submit_label' => 'Enviar',
            // Locked: each entry maps to a column of the future `leads` table.
            'fields' => ['name', 'email', 'phone', 'message'],
        ]);

        $this->section($site, null, 'contact_info', 1, [
            'address' => 'Av. Ejemplo 123, Monterrey, Nuevo León',
            'phone' => '+52 81 0000 0000',
            'email' => 'contacto@gnu.mx',
            'schedule' => 'Lunes a viernes de 9:00 a 18:00',
            'additional_info' => null,
        ]);

        $this->section($site, null, 'social_icons', 2, [
            'networks' => [
                ['network' => 'facebook', 'url' => 'https://facebook.com/gnu'],
                ['network' => 'instagram', 'url' => 'https://instagram.com/gnu'],
                ['network' => 'linkedin', 'url' => 'https://linkedin.com/company/gnu'],
            ],
        ]);

        $this->section($site, null, 'whatsapp_button', 3, [
            'phone' => '+528100000000',
            'prefilled_message' => 'Hola, quiero información sobre gas natural.',
        ]);
    }

    /**
     * Upsert a section by (site, page, type) — one instance per type per page today.
     *
     * @param  array<string, mixed>  $content
     */
    private function section(Site $site, ?Page $page, string $type, int $position, array $content): void
    {
        $site->sections()->updateOrCreate(
            ['page_id' => $page?->id, 'type' => $type],
            ['position' => $position, 'content' => $content],
        );
    }
}
