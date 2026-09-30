<?php

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $site = Site::create(['slug' => 'demo', 'name' => 'Demo']);

    $this->bare = $site->sections()->create([
        'type' => 'rich_text',
        'content' => ['body' => '<p>Sin portada.</p>', 'video_url' => null],
    ]);
    $this->filled = $site->sections()->create([
        'type' => 'rich_text',
        'content' => ['body' => '<p>Con portada.</p>', 'image_video_url' => 'media/portada-video.jpg'],
    ]);
    $this->other = $site->sections()->create([
        'type' => 'page_hero',
        'content' => ['title' => 'Hero'],
    ]);

    $this->migration = require glob(database_path('migrations/*_backfill_image_video_url_in_rich_text_sections.php'))[0];
});

it('adds an empty video cover image to the rich texts that lack it', function () {
    $this->migration->up();

    expect($this->bare->fresh()->content)
        ->toBe(['body' => '<p>Sin portada.</p>', 'video_url' => null, 'image_video_url' => null])
        ->and($this->filled->fresh()->content['image_video_url'])->toBe('media/portada-video.jpg')
        ->and($this->other->fresh()->content)->toBe(['title' => 'Hero']);
});

it('rolls back only the keys it added', function () {
    $this->migration->up();
    $this->migration->down();

    expect($this->bare->fresh()->content)->toBe(['body' => '<p>Sin portada.</p>', 'video_url' => null])
        ->and($this->filled->fresh()->content['image_video_url'])->toBe('media/portada-video.jpg');
});
