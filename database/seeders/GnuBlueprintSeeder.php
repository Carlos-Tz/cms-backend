<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Site;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the structure of the GNU home page and blog page, plus the site-wide sections.
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

        $this->aboutUs($site);

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
        ]);
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

    /**
     * Upsert a page by (site, slug). `position` orders the nav column for a root
     * page, and the child within its column for a nested one.
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
