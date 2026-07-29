<?php

namespace Database\Factories;

use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BlogPost>
 */
class BlogPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * There is no `site_id` default: `Site` has no factory (sites come from the
     * blueprint seeders), so the caller passes the site with `->for($site)`.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'slug' => Str::slug($title),
            'title' => $title,
            'excerpt' => fake()->sentence(12),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'cover_image' => 'media/placeholder-post.jpg',
            'published_at' => now()->subDays(fake()->numberBetween(1, 90)),
        ];
    }

    /**
     * Unpublished: invisible to the public API.
     */
    public function draft(): static
    {
        return $this->state(['published_at' => null]);
    }

    /**
     * Publish date in the future: also invisible to the public API.
     */
    public function scheduled(): static
    {
        return $this->state(['published_at' => now()->addWeek()]);
    }
}
