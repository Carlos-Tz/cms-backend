<?php

namespace Database\Seeders;

use App\Models\Site;
use Illuminate\Support\Str;

/**
 * Seeds the structure of the 17 GNU pages, plus the site-wide sections.
 *
 * Structure (pages, section types and their order) comes from the blueprint in
 * `.claude/skills/sitios-web-gnu`; the values below are placeholders the admin
 * replaces through the admin API. Idempotent: safe to re-run.
 */
class GnuBlueprintSeeder extends BlueprintSeeder
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

        $home = $this->page($site, 'inicio', '/', 'Inicio', 1);

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

        $this->section($site, $home, 'trust_strip', 2, [
            'title' => null,
            'items' => [
                [
                    'icon' => 'percent',
                    'title' => 'Ahorro real',
                    'text' => 'Hasta 40% menos que la gasolina',
                ],
                [
                    'icon' => 'map-pin',
                    'title' => 'Cobertura nacional',
                    'text' => '+120 estaciones en todo México',
                ],
                [
                    'icon' => 'leaf',
                    'title' => 'Energía responsable',
                    'text' => 'Menor huella de carbono desde hoy',
                ],
            ],
        ]);

        $this->section($site, $home, 'savings_simulator', 3, $this->fuelPrices(
            'Simulador de ahorro',
            'Calcula cuánto ahorras al cambiar tu combustible actual por gas natural.',
        ));

        // Carries the home page video: `video_url` is where the blueprint's
        // site-wide «soporte para videos» requirement lands, and `rich_text` is
        // the only type that has it.
        $this->section($site, $home, 'rich_text', 4, [
            'title' => null,
            'body' => '<p>Contenido pendiente de redactar.</p>',
            'video_url' => null,
            'image_video_url' => null,
        ]);

        $this->section($site, $home, 'clients_showcase', 5, [
            'title' => 'Nuestros clientes',
            'logos' => [
                [
                    'image' => 'media/placeholder-logo.png',
                    'name' => 'Cliente de ejemplo',
                    'url' => null,
                ],
            ],
        ]);

        $this->section($site, $home, 'stats_band', 6, [
            'stats' => [
                ['value' => 40, 'suffix' => '%', 'label' => 'de ahorro frente a la gasolina'],
                ['value' => 120, 'suffix' => '+', 'label' => 'estaciones en México'],
                ['value' => 3, 'suffix' => '', 'label' => 'soluciones: GNC, GND y GNV'],
            ],
            'cta_label' => 'Conoce nuestros servicios',
            'cta_href' => '/nuestros-servicios',
        ]);

        $this->section($site, $home, 'image_text', 7, [
            'enabled' => false,
            'image' => null,
            'eyebrow' => null,
            'badge' => null,
            'title' => null,
            'description' => null,
            'items' => [],
            'cta_label' => null,
            'cta_href' => null,
        ]);

        $this->section($site, $home, 'lead_capture_form', 8, [
            'title' => 'Contáctanos',
            'intro' => 'Déjanos tus datos y un asesor se comunicará contigo.',
            'submit_label' => 'Enviar',
            // Locked: each entry maps to a column of the future `leads` table.
            'fields' => ['name', 'email', 'phone', 'message'],
        ]);

        $this->services($site);
        $this->aboutUs($site);
        $this->sustainability($site);
        $this->contact($site);
        $this->stations($site);

        // The blog listing itself is served by GET /api/public/gnu/blog, so the
        // page only carries its hero. Articles are `blog_posts` rows, not pages.
        $blog = $this->page($site, 'blog', '/blog', 'Blog', 6);

        $this->section($site, $blog, 'page_hero', 1, [
            'title' => 'Blog',
            'subtitle' => 'Noticias y artículos sobre el gas natural.',
            'image' => null,
        ]);

        $this->posts($site);

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

        // Site-wide in this blueprint: the original tree lists testimonials
        // outside the navigation columns, not as a section of a single page.
        $this->section($site, null, 'testimonials', 4, [
            'title' => 'Lo que dicen nuestros clientes',
            'items' => [
                [
                    'quote' => 'Pendiente de redactar.',
                    'author' => 'Cliente de ejemplo',
                    'role' => null,
                    'photo' => null,
                ],
            ],
        ]);
    }

    /**
     * The «Nuestros servicios» column.
     *
     * GNC is the commercial priority of the blueprint and the only page carrying
     * `savings_simulator` and `stations_map` together. Its simulator keeps its
     * own reference prices; the public endpoint selects it with `?page=gnc`.
     */
    private function services(Site $site): void
    {
        $services = $this->page($site, 'nuestros-servicios', '/nuestros-servicios', 'Nuestros servicios', 2);

        $this->section($site, $services, 'page_hero', 1, [
            'title' => 'Nuestros servicios',
            'subtitle' => 'Soluciones de gas natural para tu operación.',
            'image' => null,
        ]);

        $this->section($site, $services, 'rich_text', 2, [
            'title' => null,
            'body' => '<p>Contenido pendiente de redactar.</p>',
            'video_url' => null,
            'image_video_url' => null,
        ]);

        $this->section($site, $services, 'clients_showcase', 3, [
            'title' => 'Nuestros clientes',
            'logos' => [
                ['image' => 'media/placeholder-logo.png', 'name' => 'Cliente de ejemplo', 'url' => null],
            ],
        ]);

        $gnc = $this->page($site, 'gnc', '/nuestros-servicios/gnc', 'GNC', 1, $services);

        $this->section($site, $gnc, 'page_hero', 1, [
            'title' => 'GNC',
            'subtitle' => 'Gas natural comprimido',
            'image' => null,
        ]);

        $this->section($site, $gnc, 'rich_text', 2, [
            'title' => null,
            'body' => '<p>Contenido pendiente de redactar para GNC.</p>',
            'video_url' => null,
            'image_video_url' => null,
        ]);

        $this->section($site, $gnc, 'feature_grid', 3, [
            'title' => 'Beneficios',
            'items' => [
                ['icon' => null, 'title' => 'Ahorro', 'text' => 'Pendiente de redactar.'],
                ['icon' => null, 'title' => 'Menor huella de carbono', 'text' => 'Pendiente de redactar.'],
                ['icon' => null, 'title' => 'Red de estaciones', 'text' => 'Pendiente de redactar.'],
            ],
        ]);

        $this->section($site, $gnc, 'savings_simulator', 4, $this->fuelPrices(
            'Simulador de ahorro',
            'Compara el costo de tu combustible actual contra el gas natural comprimido.',
        ));

        $this->section($site, $gnc, 'stations_map', 5, $this->map('Nuestras estaciones'));

        $this->section($site, $gnc, 'lead_capture_form', 6, [
            'title' => 'Cotiza tu suministro',
            'intro' => 'Déjanos tus datos y un asesor se comunicará contigo.',
            'submit_label' => 'Enviar',
            // Locked: each entry maps to a column of the `leads` table.
            'fields' => ['name', 'email', 'phone', 'message'],
        ]);

        $products = [
            ['gnd', 'GND', 'Gas natural distribuido', 2],
            ['gnv', 'GNV', 'Gas natural vehicular', 3],
        ];

        foreach ($products as [$slug, $title, $subtitle, $position]) {
            $page = $this->page($site, $slug, "/nuestros-servicios/{$slug}", $title, $position, $services);

            $this->section($site, $page, 'page_hero', 1, [
                'title' => $title,
                'subtitle' => $subtitle,
                'image' => null,
            ]);

            $this->section($site, $page, 'rich_text', 2, [
                'title' => null,
                'body' => "<p>Contenido pendiente de redactar para {$title}.</p>",
                'video_url' => null,
                'image_video_url' => null,
            ]);

            $this->section($site, $page, 'feature_grid', 3, [
                'title' => 'Beneficios',
                'items' => [
                    ['icon' => null, 'title' => 'Ahorro', 'text' => 'Pendiente de redactar.'],
                    ['icon' => null, 'title' => 'Menor huella de carbono', 'text' => 'Pendiente de redactar.'],
                    ['icon' => null, 'title' => 'Suministro confiable', 'text' => 'Pendiente de redactar.'],
                ],
            ]);

            $this->section($site, $page, 'lead_capture_form', 4, [
                'title' => 'Cotiza tu suministro',
                'intro' => 'Déjanos tus datos y un asesor se comunicará contigo.',
                'submit_label' => 'Enviar',
                // Locked: each entry maps to a column of the `leads` table.
                'fields' => ['name', 'email', 'phone', 'message'],
            ]);
        }
    }

    /**
     * The «Desarrollo sostenible» column: the parent page and its four children.
     *
     * Four of the five are the same page_hero + rich_text shape, so they come
     * from a table instead of five near-identical blocks. «Iniciativas de RSE»
     * is the exception: it carries a feature_grid.
     */
    private function sustainability(Site $site): void
    {
        $parent = $this->page($site, 'desarrollo-sostenible', '/desarrollo-sostenible', 'Desarrollo sostenible', 4);

        $textPages = [
            [$parent, 'Nuestro compromiso con el entorno y las comunidades.'],
            [
                $this->page($site, 'distintivo-esr', '/desarrollo-sostenible/distintivo-esr', 'Distintivo ESR', 1, $parent),
                'Empresa Socialmente Responsable.',
            ],
            [
                $this->page($site, 'ecovadis', '/desarrollo-sostenible/ecovadis', 'EcoVadis', 2, $parent),
                'Nuestra evaluación de sostenibilidad.',
            ],
            [
                $this->page($site, 'voluntariado', '/desarrollo-sostenible/voluntariado', 'Voluntariado', 3, $parent),
                'Nuestro equipo en acción.',
            ],
        ];

        foreach ($textPages as [$page, $subtitle]) {
            $this->section($site, $page, 'page_hero', 1, [
                'title' => $page->title,
                'subtitle' => $subtitle,
                'image' => null,
            ]);

            $this->section($site, $page, 'rich_text', 2, [
                'title' => null,
                'body' => "<p>Contenido pendiente de redactar para «{$page->title}».</p>",
                'video_url' => null,
                'image_video_url' => null,
            ]);
        }

        $initiatives = $this->page(
            $site,
            'iniciativas-rse',
            '/desarrollo-sostenible/iniciativas-rse',
            'Iniciativas de RSE',
            4,
            $parent,
        );

        $this->section($site, $initiatives, 'page_hero', 1, [
            'title' => 'Iniciativas de RSE',
            'subtitle' => 'Los programas que sostenemos.',
            'image' => null,
        ]);

        $this->section($site, $initiatives, 'feature_grid', 2, [
            'title' => null,
            'items' => [
                ['icon' => null, 'title' => 'Iniciativa de ejemplo', 'text' => 'Pendiente de redactar.'],
                ['icon' => null, 'title' => 'Iniciativa de ejemplo', 'text' => 'Pendiente de redactar.'],
            ],
        ]);
    }

    /**
     * The «Sobre nosotros» column: the parent page and its four children.
     *
     * `proposito-empresarial` carries a feature_grid on top of its rich_text so
     * Misión / Visión / Propósito are structured cards instead of headings buried
     * in the HTML body — the admin reorders them without touching markup.
     */
    private function aboutUs(Site $site): void
    {
        $about = $this->page($site, 'sobre-nosotros', '/sobre-nosotros', 'Sobre nosotros', 3);

        $this->section($site, $about, 'page_hero', 1, [
            'title' => 'Sobre nosotros',
            'subtitle' => 'Quiénes somos y qué nos mueve.',
            'image' => null,
        ]);

        $this->section($site, $about, 'rich_text', 2, [
            'title' => null,
            'body' => '<p>Somos un grupo mexicano dedicado a la comercialización y distribución de gas natural.</p>',
            'video_url' => null,
            'image_video_url' => null,
        ]);

        $purpose = $this->page(
            $site,
            'proposito-empresarial',
            '/sobre-nosotros/proposito-empresarial',
            'Propósito empresarial',
            1,
            $about,
        );

        $this->section($site, $purpose, 'page_hero', 1, [
            'title' => 'Propósito empresarial',
            'subtitle' => 'La razón por la que existimos.',
            'image' => null,
        ]);

        $this->section($site, $purpose, 'rich_text', 2, [
            'title' => null,
            'body' => '<p>Contenido pendiente de redactar.</p>',
            'video_url' => null,
            'image_video_url' => null,
        ]);

        $this->section($site, $purpose, 'feature_grid', 3, [
            'title' => null,
            'items' => [
                ['icon' => null, 'title' => 'Misión', 'text' => 'Pendiente de redactar.'],
                ['icon' => null, 'title' => 'Visión', 'text' => 'Pendiente de redactar.'],
                ['icon' => null, 'title' => 'Propósito', 'text' => 'Pendiente de redactar.'],
            ],
        ]);

        $values = $this->page($site, 'valores', '/sobre-nosotros/valores', 'Valores', 2, $about);

        $this->section($site, $values, 'page_hero', 1, [
            'title' => 'Valores',
            'subtitle' => 'Los principios que guían nuestras decisiones.',
            'image' => null,
        ]);

        $this->section($site, $values, 'feature_grid', 2, [
            'title' => null,
            'items' => [
                ['icon' => null, 'title' => 'Integridad', 'text' => 'Pendiente de redactar.'],
                ['icon' => null, 'title' => 'Seguridad', 'text' => 'Pendiente de redactar.'],
                ['icon' => null, 'title' => 'Compromiso', 'text' => 'Pendiente de redactar.'],
                ['icon' => null, 'title' => 'Sostenibilidad', 'text' => 'Pendiente de redactar.'],
            ],
        ]);

        $certifications = $this->page(
            $site,
            'certificaciones',
            '/sobre-nosotros/certificaciones',
            'Certificaciones',
            3,
            $about,
        );

        $this->section($site, $certifications, 'page_hero', 1, [
            'title' => 'Certificaciones',
            'subtitle' => 'Los estándares que respaldan nuestra operación.',
            'image' => null,
        ]);

        // `icon` holds the certifier logo; there is no link field by design.
        $this->section($site, $certifications, 'feature_grid', 2, [
            'title' => null,
            'items' => [
                ['icon' => null, 'title' => 'Certificación de ejemplo', 'text' => 'Pendiente de redactar.'],
                ['icon' => null, 'title' => 'Certificación de ejemplo', 'text' => 'Pendiente de redactar.'],
            ],
        ]);

        $history = $this->page(
            $site,
            'nuestra-historia',
            '/sobre-nosotros/nuestra-historia',
            'Nuestra historia',
            4,
            $about,
        );

        $this->section($site, $history, 'page_hero', 1, [
            'title' => 'Nuestra historia',
            'subtitle' => 'De dónde venimos.',
            'image' => null,
        ]);

        $this->section($site, $history, 'rich_text', 2, [
            'title' => null,
            'body' => '<p>Contenido pendiente de redactar.</p>',
            'video_url' => null,
            'image_video_url' => null,
        ]);
    }

    /**
     * The «Contacto» column. No children: the blueprint reads its two nodes
     * (Captación de leads, Mapa de estaciones) as sections of this page.
     */
    private function contact(Site $site): void
    {
        $contact = $this->page($site, 'contacto', '/contacto', 'Contacto', 5);

        $this->section($site, $contact, 'page_hero', 1, [
            'title' => 'Contacto',
            'subtitle' => 'Estamos para ayudarte.',
            'image' => null,
        ]);

        $this->section($site, $contact, 'lead_capture_form', 2, [
            'title' => 'Escríbenos',
            'intro' => 'Déjanos tus datos y un asesor se comunicará contigo.',
            'submit_label' => 'Enviar',
            // Locked: each entry maps to a column of the `leads` table.
            'fields' => ['name', 'email', 'phone', 'message'],
        ]);

        $this->section($site, $contact, 'stations_map', 3, $this->map('Dónde encontrarnos'));
    }

    /**
     * Two sample stations so the map has pins to draw. Real ones are loaded by
     * the admin through `StationController`; these are placeholders like every
     * other value in this seeder.
     */
    private function stations(Site $site): void
    {
        $stations = [
            ['Estación Monterrey Centro', 'Av. Constitución 100, Monterrey, N.L.', 25.6714, -100.3094],
            ['Estación Guadalupe', 'Av. Pablo Livas 500, Guadalupe, N.L.', 25.6768, -100.2560],
        ];

        foreach ($stations as [$name, $address, $latitude, $longitude]) {
            $site->stations()->updateOrCreate(
                ['name' => $name],
                [
                    'address' => $address,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'phone' => '+52 81 0000 0000',
                    'schedule' => 'Lunes a domingo de 6:00 a 22:00',
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * Reference prices for a `savings_simulator` section.
     *
     * `equivalence_factor` = units of natural gas equivalent to one unit of this
     * fuel. Natural gas is the baseline, so its own factor is 1.
     *
     * Each simulator section keeps its own copy: the public endpoint selects one
     * with `?page={slug}`, so GNC could quote different prices than the home page
     * without a second mechanism.
     *
     * @return array<string, mixed>
     */
    private function fuelPrices(string $title, string $intro): array
    {
        return [
            'title' => $title,
            'intro' => $intro,
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
        ];
    }

    /**
     * A `stations_map` section. `center` and `zoom` are the initial framing,
     * the admin can move it; the pins come from the `stations` table, not from here.
     *
     * @return array<string, mixed>
     */
    private function map(string $title): array
    {
        return [
            'title' => $title,
            'center' => '25.6714,-100.3094',
            'zoom' => '11',
        ];
    }

    /**
     * The four seed articles listed in the blueprint, oldest first.
     * Values are placeholders the admin replaces through the admin API.
     */
    private function posts(Site $site): void
    {
        $titles = [
            '¿Por qué elegir gas natural?',
            'Beneficios del gas natural',
            'Impacto del gas natural en el medio ambiente',
            'Gas natural vs otros combustibles',
        ];

        foreach ($titles as $index => $title) {
            $site->blogPosts()->updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'title' => $title,
                    'excerpt' => 'Resumen pendiente de redactar.',
                    'body' => "<p>Contenido pendiente de redactar para «{$title}».</p>",
                    'cover_image' => null,
                    'published_at' => now()->subDays(count($titles) - $index),
                ],
            );
        }
    }
}
