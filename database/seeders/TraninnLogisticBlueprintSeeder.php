<?php

namespace Database\Seeders;

use App\Models\Site;

/**
 * Seeds the five navigation columns of Traninn Logistic — Inicio, Nuestros
 * servicios, Sobre nosotros, Contacto and Ubicaciones — plus the site-wide
 * sections and the placeholder stations that feed the map.
 *
 * Idempotent: safe to re-run.
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

        // Nav columns: Inicio (1), Nuestros servicios (2), Sobre nosotros (3),
        // Contacto (4), Ubicaciones (5).
        $this->home($site);
        $this->services($site);
        $this->aboutUs($site);
        $this->contact($site);
        $this->locations($site);
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
     * The «Nuestros servicios» column and its three child pages.
     *
     * All three are informative: none captures leads. The form lives on the home
     * page and in the *Contacto* column, the same criterion applied to
     * *Estaciones móviles* in the vehicular site.
     *
     * No `clients_showcase` here, unlike the equivalent column of `gnu`: in this
     * site's tree «Nuestros clientes» hangs off *Inicio* and *Sobre nosotros*.
     */
    private function services(Site $site): void
    {
        $services = $this->page($site, 'nuestros-servicios', '/nuestros-servicios', 'Nuestros servicios', 2);

        $this->section($site, $services, 'page_hero', 1, [
            'title' => 'Nuestros servicios',
            'subtitle' => 'Transporte de GNC, logística y carga pesada.',
            'image' => null,
        ]);

        $this->section($site, $services, 'rich_text', 2, $this->body());

        $pages = [
            ['transporte-de-gnc', 'Transporte de GNC', 1, 'Suministro de gas natural comprimido donde lo necesites.', [
                'Unidades certificadas',
                'Cobertura regional',
                'Suministro programado',
            ]],
            ['logistica', 'Logística', 2, 'Planeación y control de tus rutas.', [
                'Planeación de rutas',
                'Monitoreo en tiempo real',
                'Reportes de operación',
            ]],
            ['carga-pesada', 'Carga pesada', 3, 'Equipo y experiencia para cargas de gran tonelaje.', [
                'Flota de gran tonelaje',
                'Maniobras especializadas',
                'Seguro de carga',
            ]],
        ];

        foreach ($pages as [$slug, $title, $position, $subtitle, $cards]) {
            $page = $this->page($site, $slug, "/nuestros-servicios/{$slug}", $title, $position, $services);

            $this->section($site, $page, 'page_hero', 1, [
                'title' => $title,
                'subtitle' => $subtitle,
                'image' => null,
            ]);

            $this->section($site, $page, 'rich_text', 2, $this->body());
            $this->section($site, $page, 'feature_grid', 3, $this->cards("Qué incluye {$title}", $cards));
        }
    }

    /**
     * The «Contacto» column. The client tree lists a single node under it —
     * «Captación de leads» — which is the `lead_capture_form`; the hero is the
     * same one every non-home page of this site carries.
     *
     * No `stations_map` here, unlike `gnu` and `gnu-vehicular`: in this site the
     * map belongs to `/ubicaciones` only.
     *
     * This is the site's second `lead_capture_form` — the other one is on the
     * home page — so `leads.source` is what tells the two apart in the inbox.
     */
    private function contact(Site $site): void
    {
        $contact = $this->page($site, 'contacto', '/contacto', 'Contacto', 4);

        $this->section($site, $contact, 'page_hero', 1, [
            'title' => 'Contacto',
            'subtitle' => 'Estamos para ayudarte.',
            'image' => null,
        ]);

        $this->section($site, $contact, 'lead_capture_form', 2, [
            'title' => 'Escríbenos',
            'intro' => 'Déjanos tus datos y un asesor se comunicará contigo.',
            'submit_label' => 'Enviar',
            // Locked: the four core entries map to columns of `leads`, and this
            // form declares no extras, so it accepts nothing beyond them.
            'fields' => ['name', 'email', 'phone', 'message'],
        ]);
    }

    /**
     * The «Ubicaciones» column. The client declared no children for it, so its
     * shape is a decision of this blueprint: a map, fed by the `stations` table.
     *
     * The pins are not fields of the section — the admin loads the real rows
     * through `POST /api/admin/stations`, and the seeder only leaves two
     * placeholders so the map has something to draw.
     */
    private function locations(Site $site): void
    {
        $locations = $this->page($site, 'ubicaciones', '/ubicaciones', 'Ubicaciones', 5);

        $this->section($site, $locations, 'page_hero', 1, [
            'title' => 'Ubicaciones',
            'subtitle' => 'Dónde operamos.',
            'image' => null,
        ]);

        $this->section($site, $locations, 'stations_map', 2, $this->map('Nuestras bases'));

        $this->stations($site);
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

    /**
     * A `feature_grid` with one placeholder card per title.
     *
     * @param  list<string>  $titles
     * @return array<string, mixed>
     */
    private function cards(string $title, array $titles): array
    {
        return [
            'title' => $title,
            'items' => array_map(fn (string $card) => [
                'icon' => null,
                'title' => $card,
                'text' => 'Contenido pendiente de redactar.',
            ], $titles),
        ];
    }

    /**
     * A `stations_map` centred on Morelia, where this site's `contact_info`
     * places the company. `center` and `zoom` are the initial framing; the
     * admin can move it.
     *
     * Each site centres its own city, so this is not a duplicate of the helper
     * in the other two seeders.
     *
     * @return array<string, mixed>
     */
    private function map(string $title): array
    {
        return [
            'title' => $title,
            'center' => '19.7028,-101.1920',
            'zoom' => '11',
        ];
    }

    /**
     * Two placeholder rows so the map has pins. These are operating bases, not
     * filling stations: the table is the same, the meaning is the site's.
     */
    private function stations(Site $site): void
    {
        $bases = [
            ['Base Morelia', 'Carretera Morelia-Salamanca km 12, Morelia, Mich.', 19.7412, -101.2500],
            ['Base Uruapan', 'Carretera Uruapan-Paracho km 5, Uruapan, Mich.', 19.4550, -102.0300],
        ];

        foreach ($bases as [$name, $address, $latitude, $longitude]) {
            $site->stations()->updateOrCreate(
                ['name' => $name],
                [
                    'address' => $address,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'phone' => '+52 443 000 0000',
                    'schedule' => 'Lunes a viernes de 8:00 a 18:00',
                    'is_active' => true,
                ],
            );
        }
    }
}
