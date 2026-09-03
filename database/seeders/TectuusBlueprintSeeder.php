<?php

namespace Database\Seeders;

use App\Models\Site;

/**
 * Seeds the «Inicio» navigation column of Tectuus plus the site-wide sections.
 * The remaining columns — Sobre nosotros, Productos and Contacto — land in
 * later phases.
 *
 * Idempotent: safe to re-run.
 */
class TectuusBlueprintSeeder extends BlueprintSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $site = Site::updateOrCreate(
            ['slug' => 'tectuus'],
            ['name' => 'Tectuus'],
        );

        // Nav columns: Inicio (1). Sobre nosotros (2), Productos (3) and
        // Contacto (4) are not migrated yet.
        $this->home($site);
        $this->globals($site);
    }

    /**
     * The «Inicio» column. Its three children in the client tree — carrusel,
     * nuestros clientes and contacto — are sections of this page, not subpages.
     * Like the other three sites, the home page carries no `page_hero`: the
     * banner carousel is its hero.
     */
    private function home(Site $site): void
    {
        $home = $this->page($site, 'inicio', '/', 'Inicio', 1);

        $this->section($site, $home, 'banner_carousel', 1, [
            'slides' => [
                [
                    'image' => 'media/placeholder-banner-tectuus.jpg',
                    'title' => 'Equipo industrial para gas natural',
                    'subtitle' => 'EDRM, compresoras y valvulería.',
                    'link_url' => null,
                    'link_label' => null,
                ],
            ],
        ]);

        // Carries the home page video: `video_url` is where the blueprint's
        // «soporte para videos» requirement lands, and `rich_text` is the only
        // type that has it. Same criterion as the other three sites.
        $this->section($site, $home, 'rich_text', 2, [
            'title' => null,
            'body' => '<p>Contenido pendiente de redactar.</p>',
            'video_url' => null,
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
            'title' => 'Contacto',
            'intro' => 'Déjanos tus datos y un asesor se comunicará contigo.',
            'submit_label' => 'Enviar',
            // Locked: the four core entries map to columns of `leads`, and this
            // form declares no extras, so it accepts nothing beyond them.
            'fields' => ['name', 'email', 'phone', 'message'],
        ]);
    }

    /**
     * Site-wide sections — rows of `sections` with `page_id = null`. They are
     * the cross-cutting requirements declared for the 4 sites; only the social
     * icons appear in the client tree, outside the main flow.
     */
    private function globals(Site $site): void
    {
        $this->section($site, null, 'contact_info', 1, [
            'address' => 'Av. Ejemplo 321, Morelia, Michoacán',
            'phone' => '+52 443 000 0000',
            'email' => 'contacto@tectuus.mx',
            'schedule' => 'Lunes a viernes de 9:00 a 18:00',
            'additional_info' => null,
        ]);

        $this->section($site, null, 'social_icons', 2, [
            'networks' => [
                ['network' => 'facebook', 'url' => 'https://facebook.com/tectuus'],
                ['network' => 'linkedin', 'url' => 'https://linkedin.com/company/tectuus'],
            ],
        ]);

        $this->section($site, null, 'whatsapp_button', 3, [
            'phone' => '+524430000000',
            'prefilled_message' => 'Hola, quiero información sobre equipo industrial.',
        ]);
    }
}
