<?php

namespace App\Http\Requests\Admin;

use App\Models\BlogPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Validates a blog post on both create and update.
 *
 * Update takes the full payload (PUT semantics) so there is no separate request
 * class. `site_id` is only accepted on create: a post never moves between sites.
 */
class BlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Derive the slug from the title when the admin does not send one.
     *
     * A generated slug that collides fails validation instead of getting a `-2`
     * suffix: the admin decides the final URL of an article.
     */
    protected function prepareForValidation(): void
    {
        if ($this->missing('slug') && $this->filled('title')) {
            $this->merge(['slug' => Str::slug($this->string('title')->value())]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $post = $this->blogPost();

        return [
            'site_id' => $post === null
                ? ['required', 'integer', 'exists:sites,id']
                : ['prohibited'],
            'slug' => [
                'required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('blog_posts')->where(
                    'site_id',
                    $post?->site_id ?? $this->integer('site_id'),
                )->ignore($post),
            ],
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            // Path returned by POST /api/admin/media, same as section images.
            'cover_image' => ['nullable', 'string', 'max:2048'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'site_id' => 'sitio',
            'slug' => 'slug',
            'title' => 'título',
            'excerpt' => 'extracto',
            'body' => 'contenido',
            'cover_image' => 'imagen de portada',
            'published_at' => 'fecha de publicación',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.unique' => 'Ya existe un artículo con ese slug en este sitio.',
            'slug.regex' => 'El slug solo admite minúsculas, números y guiones.',
            'site_id.prohibited' => 'Un artículo no puede cambiar de sitio.',
        ];
    }

    /**
     * The post being updated, or null when creating.
     */
    private function blogPost(): ?BlogPost
    {
        /** @var BlogPost|null $post */
        $post = $this->route('blog_post');

        return $post;
    }
}
