<?php

namespace Database\Seeders;

use App\Models\Site;

/**
 * Seeds the four navigation columns of Tectuus — Inicio, Sobre nosotros,
 * Productos and Contacto — plus the site-wide sections and the three product
 * categories that feed the catalogue.
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

        // Nav columns: Inicio (1), Sobre nosotros (2), Productos (3),
        // Contacto (4).
        $this->home($site);
        $this->aboutUs($site);
        $this->products($site);
        $this->contact($site);
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

        $this->section($site, $home, 'trust_strip', 2, [
            'title' => null,
            'items' => [],
        ]);

        // Carries the home page video: `video_url` is where the blueprint's
        // «soporte para videos» requirement lands, and `rich_text` is the only
        // type that has it. Same criterion as the other three sites.
        $this->section($site, $home, 'rich_text', 3, [
            'title' => null,
            'body' => '<p>Contenido pendiente de redactar.</p>',
            'video_url' => null,
            'image_video_url' => null,
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

        $this->section($site, $home, 'stats_band', 5, [
            'stats' => [],
            'cta_label' => null,
            'cta_href' => null,
        ]);

        $this->section($site, $home, 'image_text', 6, [
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

        $this->section($site, $home, 'lead_capture_form', 7, [
            'title' => 'Contacto',
            'intro' => 'Déjanos tus datos y un asesor se comunicará contigo.',
            'submit_label' => 'Enviar',
            // Locked: the four core entries map to columns of `leads`, and this
            // form declares no extras, so it accepts nothing beyond them.
            'fields' => ['name', 'email', 'phone', 'message'],
        ]);
    }

    /**
     * The «Sobre nosotros» column. The client tree hangs a single child off it,
     * «¿Quiénes somos?», which is the content of the column head and not a
     * subpage: same reading as «Captación de leads» under *Contacto* in Traninn
     * Logistic.
     *
     * The `rich_text` carries this column's video and the gallery its photos —
     * the two things the phase asked for beyond the copy itself.
     */
    private function aboutUs(Site $site): void
    {
        $about = $this->page($site, 'sobre-nosotros', '/sobre-nosotros', 'Sobre nosotros', 2);

        $this->section($site, $about, 'page_hero', 1, [
            'title' => '¿Quiénes somos?',
            'subtitle' => 'Historia, misión, visión y valores.',
            'image' => null,
        ]);

        $this->section($site, $about, 'rich_text', 2, [
            'title' => null,
            'body' => '<p>Contenido pendiente de redactar.</p>',
            'video_url' => null,
            'image_video_url' => null,
        ]);

        $this->section($site, $about, 'image_gallery', 3, [
            'title' => 'Nuestra empresa',
            'images' => [
                ['image' => 'media/placeholder-foto-1.jpg', 'caption' => null, 'alt' => null],
                ['image' => 'media/placeholder-foto-2.jpg', 'caption' => null, 'alt' => null],
            ],
        ]);
    }

    /**
     * The «Productos» column. Its three children in the client tree — EDRM,
     * compresoras y valvulería — are **rows of `product_categories`**, not pages
     * and not a `product_catalog` section: the admin creates, edits and deletes
     * them, so they are data. Literal precedent of `/blog`, whose page carries
     * only a hero while the articles are rows of `blog_posts`.
     *
     * Consequence worth stating: the categories do not appear in
     * `GET {site}/menu`, which derives from `pages`. The front builds that
     * submenu from `GET {site}/product-categories`.
     */
    private function products(Site $site): void
    {
        $products = $this->page($site, 'productos', '/productos', 'Productos', 3);

        $this->section($site, $products, 'page_hero', 1, [
            'title' => 'Productos',
            'subtitle' => 'Equipo industrial para gas natural.',
            'image' => null,
        ]);

        // The three categories the client declared. The products inside them are
        // loaded by the admin through POST /api/admin/products, same as the
        // stations of the other sites.
        $categories = [
            ['edrm', 'EDRM', 1, 'Estaciones de regulación y medición.'],
            ['compresoras', 'Compresoras', 2, 'Equipos de compresión de gas natural.'],
            ['valvuleria', 'Valvulería', 3, 'Válvulas y accesorios para gas natural.'],
        ];

        foreach ($categories as [$slug, $name, $position, $description]) {
            $site->productCategories()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => $description,
                    'image' => null,
                    'position' => $position,
                ],
            );
        }
    }

    /**
     * The «Contacto» column. The client tree lists a single node under it —
     * «Captación de leads» — which is the `lead_capture_form`.
     *
     * This is the site's second form, so `leads.source` is what tells it apart
     * from the one on the home page in the inbox. No new code: that mechanism is
     * already generic.
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
