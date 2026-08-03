<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Site;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the blog slice of the GNU Vehicular site: the site itself, its `/blog`
 * page and the four articles listed in the blueprint.
 *
 * Partial on purpose. `references/gnu-vehicular.md` is still the client's raw
 * navigation tree, and the skill migrates it once GNU validates the model, so
 * the rest of this site's pages are not seeded yet.
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
        $blog = Page::updateOrCreate(
            ['site_id' => $site->id, 'slug' => 'blog'],
            ['path' => '/blog', 'title' => 'Blog', 'position' => 5, 'is_published' => true],
        );

        $site->sections()->updateOrCreate(
            ['page_id' => $blog->id, 'type' => 'page_hero'],
            [
                'position' => 1,
                'content' => [
                    'title' => 'Blog',
                    'subtitle' => 'Noticias y guías sobre gas natural vehicular.',
                    'image' => null,
                ],
            ],
        );

        $this->posts($site);
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
}
