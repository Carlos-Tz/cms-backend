<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Site;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the whole GNU Vehicular site: its five navigation columns and 14 pages,
 * the site-wide sections, the four blog articles and two sample stations for
 * the map.
 *
 * Idempotent: safe to re-run.
 */
class GnuVehicularBlueprintSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $site = Site::updateOrCreate(
            ['slug' => 'gnu-vehicular'],
            ['name' => 'GNU Gas Natural Vehicular'],
        );

        // Nav columns: Inicio (1), Nuestros servicios (2), Ubicaciones (3),
        // Contacto (4), Blog (5) — one column fewer than the institutional site.
        $this->home($site);
        $this->services($site);
        $this->locations($site);
        $this->contact($site);

        $blog = $this->page($site, 'blog', '/blog', 'Blog', 5);

        $this->section($site, $blog, 'page_hero', 1, [
            'title' => 'Blog',
            'subtitle' => 'Noticias y guías sobre gas natural vehicular.',
            'image' => null,
        ]);

        $this->posts($site);
        $this->globals($site);
        $this->stations($site);
    }

    /**
     * The «Inicio» column. Like the institutional site, the home page carries no
     * `page_hero`: the banner carousel is its hero.
     */
    private function home(Site $site): void
    {
        $home = $this->page($site, 'inicio', '/', 'Inicio', 1);

        $this->section($site, $home, 'banner_carousel', 1, [
            'slides' => [
                [
                    'image' => 'media/placeholder-banner-gnv.jpg',
                    'title' => 'Convierte tu vehículo a gas natural',
                    'subtitle' => 'Menos costo por kilómetro, menos emisiones.',
                    'link_url' => null,
                    'link_label' => null,
                ],
            ],
        ]);

        // Own reference prices: each simulator instance keeps its own, so this
        // site can quote a different price than the institutional one.
        $this->section($site, $home, 'savings_simulator', 2, [
            'title' => 'Simulador de ahorro',
            'intro' => 'Compara lo que gastas hoy contra lo que gastarías con gas natural vehicular.',
            'fuel_prices' => [
                [
                    'fuel' => 'gas-natural',
                    'label' => 'Gas natural vehicular',
                    'price_per_unit' => 13.20,
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

        // «Programa de recompensas» is a child of Inicio, so it is a section, and
        // it is a grid of cards — `feature_grid` covers it without a new type.
        $this->section($site, $home, 'feature_grid', 3, [
            'title' => 'Programa de recompensas',
            'items' => [
                [
                    'icon' => null,
                    'title' => 'Acumula por cada carga',
                    'text' => 'Contenido pendiente de redactar.',
                ],
                [
                    'icon' => null,
                    'title' => 'Canjea tus puntos',
                    'text' => 'Contenido pendiente de redactar.',
                ],
                [
                    'icon' => null,
                    'title' => 'Beneficios para flotillas',
                    'text' => 'Contenido pendiente de redactar.',
                ],
            ],
        ]);

        // Section of the home page here, site-wide in the institutional site.
        // The divergence follows each source file instead of harmonising them.
        $this->section($site, $home, 'testimonials', 4, [
            'title' => 'Lo que dicen nuestros conductores',
            'items' => [
                [
                    'quote' => 'Pendiente de redactar.',
                    'author' => 'Cliente de ejemplo',
                    'role' => null,
                    'photo' => null,
                ],
            ],
        ]);

        $this->section($site, $home, 'stations_map', 5, $this->map('Mapa de estaciones'));

        $this->section($site, $home, 'lead_capture_form', 6, $this->leadForm(
            'Contacto',
            'Déjanos tus datos y un asesor se comunicará contigo.',
        ));
    }

    /**
     * The «Nuestros servicios» column — the only three-level branch of the
     * project: *Instala tu equipo de GNV* hangs off the column, and *Cotiza tu
     * equipo* and *Financiamiento* hang off that page.
     *
     * No `clients_showcase` here, unlike the institutional column: that type is
     * declared for `gnu`, `traninn-logistic` and `tectuus`, not for this site.
     */
    private function services(Site $site): void
    {
        $services = $this->page($site, 'nuestros-servicios', '/nuestros-servicios', 'Nuestros servicios', 2);

        $this->section($site, $services, 'page_hero', 1, [
            'title' => 'Nuestros servicios',
            'subtitle' => 'Conversión, flotillas y suministro móvil de gas natural vehicular.',
            'image' => null,
        ]);

        $this->section($site, $services, 'rich_text', 2, $this->body());

        $this->installation($site, $services);

        $fleets = $this->page(
            $site,
            'flotillas-empresariales',
            '/nuestros-servicios/flotillas-empresariales',
            'Flotillas empresariales',
            2,
            $services,
        );

        $this->section($site, $fleets, 'page_hero', 1, [
            'title' => 'Flotillas empresariales',
            'subtitle' => 'Convierte tu flotilla y baja tu costo por kilómetro.',
            'image' => null,
        ]);

        $this->section($site, $fleets, 'rich_text', 2, $this->body());
        $this->section($site, $fleets, 'feature_grid', 3, $this->cards('Beneficios para tu flotilla', [
            'Ahorro por unidad',
            'Mantenimiento y soporte',
            'Reportes de consumo',
        ]));

        // The only B2B page of the column: it captures, the informative ones do not.
        $this->section($site, $fleets, 'lead_capture_form', 4, $this->leadForm(
            'Cotiza para tu flotilla',
            'Cuéntanos cuántas unidades tienes y un asesor se comunicará contigo.',
            ['fleet_size'],
        ));

        $mobile = $this->page(
            $site,
            'estaciones-moviles',
            '/nuestros-servicios/estaciones-moviles',
            'Estaciones móviles',
            3,
            $services,
        );

        $this->section($site, $mobile, 'page_hero', 1, [
            'title' => 'Estaciones móviles',
            'subtitle' => 'Suministro de gas natural donde opera tu flotilla.',
            'image' => null,
        ]);

        $this->section($site, $mobile, 'rich_text', 2, $this->body());
        $this->section($site, $mobile, 'feature_grid', 3, $this->cards('Cómo funciona', [
            'Evaluación del sitio',
            'Instalación de la unidad',
            'Suministro programado',
        ]));
    }

    /**
     * «Instala tu equipo de GNV» and its two child pages, the third level.
     *
     * *Cotiza tu equipo* is a `lead_capture_form`, not a type of its own — same
     * pattern as «Cotiza tu suministro» in the institutional site. There is no
     * price calculation: the visitor leaves their data and an advisor quotes.
     * *Financiamiento* is a `feature_grid`: the plans are structured cards.
     * Both discard the `equipment_quote` and `financing` candidates.
     */
    private function installation(Site $site, Page $parent): void
    {
        $install = $this->page(
            $site,
            'instala-tu-equipo-de-gnv',
            '/nuestros-servicios/instala-tu-equipo-de-gnv',
            'Instala tu equipo de GNV',
            1,
            $parent,
        );

        $this->section($site, $install, 'page_hero', 1, [
            'title' => 'Instala tu equipo de GNV',
            'subtitle' => 'Conversión certificada para tu vehículo.',
            'image' => null,
        ]);

        $this->section($site, $install, 'rich_text', 2, $this->body());
        $this->section($site, $install, 'feature_grid', 3, $this->cards('Qué incluye la conversión', [
            'Equipo certificado',
            'Instalación en taller autorizado',
            'Garantía y revisiones',
        ]));

        $quote = $this->page(
            $site,
            'cotiza-tu-equipo',
            '/nuestros-servicios/instala-tu-equipo-de-gnv/cotiza-tu-equipo',
            'Cotiza tu equipo',
            1,
            $install,
        );

        $this->section($site, $quote, 'page_hero', 1, [
            'title' => 'Cotiza tu equipo',
            'subtitle' => null,
            'image' => null,
        ]);

        $this->section($site, $quote, 'rich_text', 2, $this->body());
        $this->section($site, $quote, 'lead_capture_form', 3, $this->leadForm(
            'Cotiza tu equipo',
            'Déjanos los datos de tu vehículo y te enviamos una cotización.',
            ['vehicle_description'],
        ));

        $financing = $this->page(
            $site,
            'financiamiento',
            '/nuestros-servicios/instala-tu-equipo-de-gnv/financiamiento',
            'Financiamiento',
            2,
            $install,
        );

        $this->section($site, $financing, 'page_hero', 1, [
            'title' => 'Financiamiento',
            'subtitle' => 'Paga tu conversión a plazos.',
            'image' => null,
        ]);

        $this->section($site, $financing, 'rich_text', 2, $this->body());
        $this->section($site, $financing, 'feature_grid', 3, $this->cards('Planes disponibles', [
            'Pago de contado',
            'Plan a 12 meses',
            'Plan para flotillas',
        ]));
    }

    /**
     * The «Ubicaciones» column and its four child pages. Unlike *Inicio* and
     * *Contacto*, the children of this column are pages, not sections — the
     * classification rule of `gnu.md`. First nesting level of this site.
     *
     * Morelia, Uruapan and Talleres are open `rich_text`: the client has not
     * defined a shape for them, and free content does not need one.
     */
    private function locations(Site $site): void
    {
        $locations = $this->page($site, 'ubicaciones', '/ubicaciones', 'Ubicaciones', 3);

        $this->section($site, $locations, 'page_hero', 1, [
            'title' => 'Ubicaciones',
            'subtitle' => 'Nuestras estaciones y talleres en Michoacán.',
            'image' => null,
        ]);

        $this->section($site, $locations, 'stations_map', 2, $this->map('Dónde encontrarnos'));

        $pages = [
            ['estaciones-morelia', 'Estaciones Morelia', 1],
            ['estacion-uruapan', 'Estación Uruapan', 2],
        ];

        foreach ($pages as [$slug, $title, $position]) {
            $page = $this->page($site, $slug, "/ubicaciones/{$slug}", $title, $position, $locations);

            $this->section($site, $page, 'page_hero', 1, [
                'title' => $title,
                'subtitle' => null,
                'image' => null,
            ]);

            $this->section($site, $page, 'rich_text', 2, [
                'title' => null,
                'body' => '<p>Contenido pendiente de redactar.</p>',
                'video_url' => null,
            ]);
        }

        $this->scheduling($site, $locations);

        $workshops = $this->page($site, 'talleres', '/ubicaciones/talleres', 'Talleres', 4, $locations);

        $this->section($site, $workshops, 'page_hero', 1, [
            'title' => 'Talleres',
            'subtitle' => null,
            'image' => null,
        ]);

        $this->section($site, $workshops, 'rich_text', 2, [
            'title' => null,
            'body' => '<p>Contenido pendiente de redactar.</p>',
            'video_url' => null,
        ]);
    }

    /**
     * «Agenda tu verificación». The only page carrying `service_scheduling`,
     * the section that tells the front-end where the booking widget goes.
     *
     * The bookable hours are not content: they live in `Appointment::SLOTS` and
     * the front reads them from `GET {site}/appointments/availability`.
     */
    private function scheduling(Site $site, Page $parent): void
    {
        $page = $this->page(
            $site,
            'agenda-tu-verificacion',
            '/ubicaciones/agenda-tu-verificacion',
            'Agenda tu verificación',
            3,
            $parent,
        );

        $this->section($site, $page, 'page_hero', 1, [
            'title' => 'Agenda tu verificación',
            'subtitle' => 'Elige estación, día y horario.',
            'image' => null,
        ]);

        $this->section($site, $page, 'service_scheduling', 2, [
            'title' => 'Agenda tu verificación',
            'intro' => 'Atendemos de lunes a sábado en horarios de dos horas. Cada horario recibe una unidad.',
            'success_message' => 'Tu cita quedó registrada. Te esperamos.',
        ]);
    }

    /**
     * The «Contacto» column. Its three nodes — captación de leads, facturación
     * and mapa de estaciones — are sections of this page, not subpages: under
     * *Inicio* and *Contacto* the client listed page content, not children.
     */
    private function contact(Site $site): void
    {
        $contact = $this->page($site, 'contacto', '/contacto', 'Contacto', 4);

        $this->section($site, $contact, 'page_hero', 1, [
            'title' => 'Contacto',
            'subtitle' => 'Estamos para ayudarte.',
            'image' => null,
        ]);

        $this->section($site, $contact, 'lead_capture_form', 2, $this->leadForm(
            'Captación de leads',
            'Cuéntanos qué necesitas y un asesor se comunicará contigo.',
        ));

        // «Facturación» is a `rich_text`, not a type of its own: instructions
        // plus a link to the billing portal is free content. Same criterion as
        // «Programa de recompensas» — an unaudited candidate stays out of the
        // catalogue while a generic type covers it.
        $this->section($site, $contact, 'rich_text', 3, [
            'title' => 'Facturación',
            'body' => '<p>Contenido pendiente de redactar: instrucciones de facturación y liga al portal.</p>',
            'video_url' => null,
        ]);

        $this->section($site, $contact, 'stations_map', 4, $this->map('Mapa de estaciones'));
    }

    /**
     * Site-wide sections (`page_id = null`). The blueprint declares these three
     * as cross-cutting requirements of all four sites. `testimonials` is not
     * here: in this site it belongs to the home page.
     */
    private function globals(Site $site): void
    {
        $this->section($site, null, 'contact_info', 1, [
            'address' => 'Av. Ejemplo 456, Morelia, Michoacán',
            'phone' => '+52 443 000 0000',
            'email' => 'contacto@gnuvehicular.mx',
            'schedule' => 'Lunes a sábado de 7:00 a 21:00',
            'additional_info' => null,
        ]);

        $this->section($site, null, 'social_icons', 2, [
            'networks' => [
                ['network' => 'facebook', 'url' => 'https://facebook.com/gnuvehicular'],
                ['network' => 'instagram', 'url' => 'https://instagram.com/gnuvehicular'],
            ],
        ]);

        $this->section($site, null, 'whatsapp_button', 3, [
            'phone' => '+524430000000',
            'prefilled_message' => 'Hola, quiero información sobre gas natural vehicular.',
        ]);
    }

    /**
     * An empty `rich_text` body. Every page of the blueprint carries one and the
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
     * A `lead_capture_form` section.
     *
     * `fields` is locked: the four core entries map to columns of `leads`, and
     * `$extra` declares the fields this particular form asks for on top of them
     * — they travel in `leads.details`. The declaration is binding: the public
     * endpoint rejects any key a form did not declare.
     *
     * @param  list<string>  $extra
     * @return array<string, mixed>
     */
    private function leadForm(string $title, string $intro, array $extra = []): array
    {
        return [
            'title' => $title,
            'intro' => $intro,
            'submit_label' => 'Enviar',
            // `message` stays last: it reads as the closing field of a form.
            'fields' => ['name', 'email', 'phone', ...$extra, 'message'],
        ];
    }

    /**
     * A `stations_map` section centred on Morelia. `center` and `zoom` are
     * locked layout values; the pins come from the `stations` table.
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
     * Two sample stations so the map has pins. The blueprint names Morelia and
     * Uruapan under «Ubicaciones»; the real rows are loaded by the admin through
     * `StationController`.
     */
    private function stations(Site $site): void
    {
        $stations = [
            ['Estación Morelia', 'Av. Madero Poniente 1000, Morelia, Mich.', 19.7028, -101.1920],
            ['Estación Uruapan', 'Av. Latinoamericana 300, Uruapan, Mich.', 19.4114, -102.0563],
        ];

        foreach ($stations as [$name, $address, $latitude, $longitude]) {
            $site->stations()->updateOrCreate(
                ['name' => $name],
                [
                    'address' => $address,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'phone' => '+52 443 000 0000',
                    'schedule' => 'Lunes a domingo de 6:00 a 22:00',
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * The four seed articles listed in the blueprint, oldest first.
     * Values are placeholders the admin replaces through the admin API.
     */
    private function posts(Site $site): void
    {
        $titles = [
            'Verificación anual',
            'Transporte sostenible',
            'Seguridad del GNV',
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

    /**
     * Upsert a page by (site, slug). `position` orders the nav column.
     *
     * ponytail: duplicated from GnuBlueprintSeeder. Extract a base seeder when
     * the third site lands — two copies do not pay for an abstraction yet.
     */
    private function page(
        Site $site,
        string $slug,
        string $path,
        string $title,
        int $position,
        ?Page $parent = null,
    ): Page {
        return Page::updateOrCreate(
            ['site_id' => $site->id, 'slug' => $slug],
            [
                'parent_id' => $parent?->id,
                'path' => $path,
                'title' => $title,
                'position' => $position,
                'is_published' => true,
            ],
        );
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
