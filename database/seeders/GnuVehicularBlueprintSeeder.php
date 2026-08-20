<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Site;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the GNU Vehicular site: its home page with the six sections of the
 * blueprint, the site-wide sections, the `/blog` page with its four articles,
 * and two sample stations for the map.
 *
 * Still partial. The «Nuestros servicios», «Ubicaciones» and «Contacto» columns
 * of `references/gnu-vehicular.md` are not migrated yet — they carry the four
 * unaudited section candidates and the only three-level tree of the project.
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

        // `center` and `zoom` are locked layout values; the pins come from the
        // `stations` table, not from this section.
        $this->section($site, $home, 'stations_map', 5, [
            'title' => 'Mapa de estaciones',
            'center' => '19.7028,-101.1920',
            'zoom' => '11',
        ]);

        $this->section($site, $home, 'lead_capture_form', 6, [
            'title' => 'Contacto',
            'intro' => 'Déjanos tus datos y un asesor se comunicará contigo.',
            'submit_label' => 'Enviar',
            // Locked: each entry maps to a column of the `leads` table.
            'fields' => ['name', 'email', 'phone', 'message'],
        ]);
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
