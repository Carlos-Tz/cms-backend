<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Nav order the client asked for: the «Nuestros servicios» submenu. */
    private const ORDER = ['gnv' => 1, 'gnc' => 2, 'gnd' => 3];

    /** The blueprint order shipped before that request. */
    private const PREVIOUS = ['gnc' => 1, 'gnd' => 2, 'gnv' => 3];

    public function up(): void
    {
        $this->reorder(self::ORDER);
    }

    public function down(): void
    {
        $this->reorder(self::PREVIOUS);
    }

    /**
     * Page `position` is blueprint, so the admin API cannot change it and a
     * re-seed is the only other route — and that would overwrite every section
     * the admin has edited. This touches one column and nothing else.
     *
     * Query builder, not the Page model, so it keeps working if the model changes.
     *
     * @param  array<string, int>  $positions
     */
    private function reorder(array $positions): void
    {
        $siteId = DB::table('sites')->where('slug', 'gnu')->value('id');

        // Fresh database: the blueprint seeder already writes the new order.
        if ($siteId === null) {
            return;
        }

        foreach ($positions as $slug => $position) {
            DB::table('pages')
                ->where('site_id', $siteId)
                ->where('slug', $slug)
                ->update(['position' => $position]);
        }
    }
};
