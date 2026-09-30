<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Give every existing `rich_text` the new `image_video_url` key, so the public
     * API returns it on all rows and not only after the first admin save.
     */
    public function up(): void
    {
        $this->rewriteContent(fn (array $content) => array_key_exists('image_video_url', $content)
            ? null
            : $content + ['image_video_url' => null]);
    }

    /**
     * Removes the key only where it is still null: a cover image the admin already
     * set is data, not something this migration created.
     */
    public function down(): void
    {
        $this->rewriteContent(function (array $content) {
            if (! array_key_exists('image_video_url', $content) || $content['image_video_url'] !== null) {
                return null;
            }

            unset($content['image_video_url']);

            return $content;
        });
    }

    /**
     * Rewrite the JSON content of every `rich_text` row; a null from `$transform`
     * leaves the row untouched. Query builder, not the Section model, so this
     * migration keeps working when the model changes.
     *
     * @param  Closure(array<string, mixed>): (array<string, mixed>|null)  $transform
     */
    private function rewriteContent(Closure $transform): void
    {
        DB::table('sections')
            ->where('type', 'rich_text')
            ->chunkById(100, function ($rows) use ($transform) {
                foreach ($rows as $row) {
                    $content = $transform(json_decode($row->content, true));

                    if ($content !== null) {
                        DB::table('sections')->where('id', $row->id)->update(['content' => json_encode($content)]);
                    }
                }
            });
    }
};
