<?php

namespace Database\Seeders;

use App\Models\Site;

/**
 * Seeds the «Inicio» and «Sobre nosotros» columns of Traninn Logistic, plus the
 * site-wide sections.
 *
 * Partial on purpose: the other three columns (Nuestros servicios, Contacto,
 * Ubicaciones) are still raw client tree in
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

        // Nav columns: Inicio (1), Sobre nosotros (3). Positions 2, 4 and 5 —
        // Nuestros servicios, Contacto and Ubicaciones — are not seeded yet, and
        // the public menu simply skips them.
        $this->home($site);
        $this->aboutUs($site);
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
     * The «Sobre nosotros» column and its three child pages.
     *
     * *Nuestros clientes* is the exception of the classification rule of
     * `gnu.md`: its name matches a catalogue type, so it is a section of the
     * column head, not a fourth child page. Same reading as «Nuestros clientes»
     * under *Nuestros servicios* in the institutional site.
     */
    private function aboutUs(Site $site): void
    {
        $about = $this->page($site, 'sobre-nosotros', '/sobre-nosotros', 'Sobre nosotros', 3);

        $this->section($site, $about, 'page_hero', 1, [
            'title' => 'Sobre nosotros',
            'subtitle' => 'Quiénes somos y cómo trabajamos.',
            'image' => null,
        ]);

        $this->section($site, $about, 'rich_text', 2, $this->body());

        // Each client carries its own description and testimonial: the three
        // fields are nullable in the type, so the wall of logos on the home page
        // stays valid leaving them empty.
        $this->section($site, $about, 'clients_showcase', 3, [
            'title' => 'Nuestros clientes',
            'logos' => [
                [
                    'image' => 'media/placeholder-logo.png',
                    'name' => 'Cliente de ejemplo',
                    'url' => null,
                    'description' => 'Descripción pendiente de redactar.',
                    'quote' => 'Testimonio pendiente de redactar.',
                    'quote_author' => 'Contacto de ejemplo',
                ],
                [
                    'image' => 'media/placeholder-logo.png',
                    'name' => 'Segundo cliente de ejemplo',
                    'url' => null,
                    'description' => 'Descripción pendiente de redactar.',
                    'quote' => 'Testimonio pendiente de redactar.',
                    'quote_author' => 'Contacto de ejemplo',
                ],
            ],
        ]);

        $whatWeDo = $this->page(
            $site,
            'que-hacemos',
            '/sobre-nosotros/que-hacemos',
            '¿Qué hacemos?',
            1,
            $about,
        );

        $this->section($site, $whatWeDo, 'page_hero', 1, [
            'title' => '¿Qué hacemos?',
            'subtitle' => 'Transporte, logística y carga pesada de gas natural.',
            'image' => null,
        ]);

        // On an inner page the carousel is a content block, not the hero: the
        // «carousel is the hero» rule applies only to `/`, which carries no
        // `page_hero` at all.
        $this->section($site, $whatWeDo, 'banner_carousel', 2, $this->banners('Transporte de GNC'));
        $this->section($site, $whatWeDo, 'rich_text', 3, $this->body());
        $this->section($site, $whatWeDo, 'image_gallery', 4, $this->gallery('Nuestra operación'));

        $purpose = $this->page(
            $site,
            'proposito-empresarial',
            '/sobre-nosotros/proposito-empresarial',
            'Propósito empresarial',
            2,
            $about,
        );

        $this->section($site, $purpose, 'page_hero', 1, [
            'title' => 'Propósito empresarial',
            'subtitle' => null,
            'image' => null,
        ]);

        $this->section($site, $purpose, 'rich_text', 2, $this->body());
        $this->section($site, $purpose, 'image_gallery', 3, $this->gallery('Nuestro equipo'));

        $history = $this->page(
            $site,
            'nuestra-historia',
            '/sobre-nosotros/nuestra-historia',
            'Nuestra historia',
            3,
            $about,
        );

        $this->section($site, $history, 'page_hero', 1, [
            'title' => 'Nuestra historia',
            'subtitle' => null,
            'image' => null,
        ]);

        $this->section($site, $history, 'banner_carousel', 2, $this->banners('Nuestra trayectoria'));
        $this->section($site, $history, 'rich_text', 3, $this->body());
        $this->section($site, $history, 'image_gallery', 4, $this->gallery('Línea del tiempo'));
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

    /**
     * An empty `rich_text` body. Every page of this column carries one and the
     * copy is written by the admin, so the placeholder is always the same.
     *
     * @return array<string, mixed>
     */
    private function body(): array
    {
        return [
            'title' => null,
            'body' => '<p>Contenido pendiente de redactar.</p>',
            'video_url' => null,
        ];
    }

    /**
     * A `banner_carousel` with one placeholder slide.
     *
     * @return array<string, mixed>
     */
    private function banners(string $title): array
    {
        return [
            'slides' => [
                [
                    'image' => 'media/placeholder-banner-traninn.jpg',
                    'title' => $title,
                    'subtitle' => null,
                    'link_url' => null,
                    'link_label' => null,
                ],
            ],
        ];
    }

    /**
     * An `image_gallery` with two placeholder images. The admin adds, removes
     * and reorders them; how the grid is laid out is the front-end's call, so
     * the type carries no `layout` or `columns`.
     *
     * @return array<string, mixed>
     */
    private function gallery(string $title): array
    {
        return [
            'title' => $title,
            'images' => [
                ['image' => 'media/placeholder-foto-1.jpg', 'caption' => null, 'alt' => null],
                ['image' => 'media/placeholder-foto-2.jpg', 'caption' => null, 'alt' => null],
            ],
        ];
    }
}
