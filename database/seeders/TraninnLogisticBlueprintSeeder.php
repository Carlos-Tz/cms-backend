<?php

namespace Database\Seeders;

use App\Models\Site;

/**
 * Seeds the «Inicio» column of Traninn Logistic and the site-wide sections.
 *
 * Partial on purpose: the other four columns (Nuestros servicios, Sobre
 * nosotros, Contacto, Ubicaciones) are still raw client tree in
 * `.claude/skills/sitios-web-gnu/references/traninn-logistic.md` and land in a
 * later pass. Idempotent: safe to re-run.
 */
class TraninnLogisticBlueprintSeeder extends BlueprintSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $site = Site::updateOrCreate(
            ['slug' => 'traninn-logistic'],
            ['name' => 'Traninn Logistic'],
        );

        $this->home($site);
        $this->globals($site);
    }

    /**
     * The «Inicio» column. Its four children in the client tree — carrusel,
     * simulador, nuestros clientes and contacto — are sections of this page, not
     * subpages. Like the other two sites, the home page carries no `page_hero`:
     * the banner carousel is its hero.
     */
    private function home(Site $site): void
    {
        $home = $this->page($site, 'inicio', '/', 'Inicio', 1);

        $this->section($site, $home, 'banner_carousel', 1, [
            'slides' => [
                [
                    'image' => 'media/placeholder-banner-traninn.jpg',
                    'title' => 'Transporte de gas natural comprimido',
                    'subtitle' => 'Logística y carga pesada para tu operación.',
                    'link_url' => null,
                    'link_label' => null,
                ],
            ],
        ]);

        // Own reference prices, quoted per m³ of transported gas: this site sells
        // freight, not vehicle conversions, so its numbers differ from the other
        // two. The admin keeps them current through the admin API.
        $this->section($site, $home, 'savings_simulator', 2, [
            'title' => 'Simulador de ahorro',
            'intro' => 'Compara el costo de tu combustible actual contra el gas natural.',
            'fuel_prices' => [
                [
                    'fuel' => 'gas-natural',
                    'label' => 'Gas natural comprimido',
                    'price_per_unit' => 12.80,
                    'unit' => 'm³',
                    'equivalence_factor' => 1,
                ],
                [
                    'fuel' => 'diesel',
                    'label' => 'Diésel',
                    'price_per_unit' => 26.40,
                    'unit' => 'litro',
                    'equivalence_factor' => 0.95,
                ],
                [
                    'fuel' => 'gasolina',
                    'label' => 'Gasolina',
                    'price_per_unit' => 24.90,
                    'unit' => 'litro',
                    'equivalence_factor' => 0.84,
                ],
            ],
        ]);

        // Carries the home page video: `video_url` is where the blueprint's
        // «soporte para videos» requirement lands, and `rich_text` is the only
        // type that has it. Same criterion as the institutional site.
        $this->section($site, $home, 'rich_text', 3, [
            'title' => null,
            'body' => '<p>Contenido pendiente de redactar.</p>',
            'video_url' => null,
        ]);

        $this->section($site, $home, 'clients_showcase', 4, [
            'title' => 'Nuestros clientes',
            'logos' => [
                [
                    'image' => 'media/placeholder-logo.png',
                    'name' => 'Cliente de ejemplo',
                    'url' => null,
                ],
            ],
        ]);

        $this->section($site, $home, 'lead_capture_form', 5, [
            'title' => 'Contacto',
            'intro' => 'Déjanos tus datos y un asesor se comunicará contigo.',
            'submit_label' => 'Enviar',
            // Locked: the four core entries map to columns of `leads`, and this
            // form declares no extras, so it accepts nothing beyond them.
            'fields' => ['name', 'email', 'phone', 'message'],
        ]);
    }

    /**
     * Site-wide sections (`page_id = null`). The three cross-cutting
     * requirements of all four sites. No `testimonials` and no `stations_map`:
     * this site declares neither.
     */
    private function globals(Site $site): void
    {
        $this->section($site, null, 'contact_info', 1, [
            'address' => 'Av. Ejemplo 789, Morelia, Michoacán',
            'phone' => '+52 443 000 0000',
            'email' => 'contacto@traninnlogistic.mx',
            'schedule' => 'Lunes a viernes de 8:00 a 18:00',
            'additional_info' => null,
        ]);

        $this->section($site, null, 'social_icons', 2, [
            'networks' => [
                ['network' => 'facebook', 'url' => 'https://facebook.com/traninnlogistic'],
                ['network' => 'linkedin', 'url' => 'https://linkedin.com/company/traninnlogistic'],
            ],
        ]);

        $this->section($site, null, 'whatsapp_button', 3, [
            'phone' => '+524430000000',
            'prefilled_message' => 'Hola, quiero información sobre transporte de gas natural.',
        ]);
    }
}
