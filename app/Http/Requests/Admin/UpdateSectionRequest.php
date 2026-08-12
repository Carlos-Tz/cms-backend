<?php

namespace App\Http\Requests\Admin;

use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

/**
 * Validates the `content` payload of a section against the schema of its type.
 *
 * The section `type` itself is never accepted from the client: it belongs to the
 * blueprint. Locked (`fijo`) fields are simply absent from the rules, so they are
 * dropped from the payload and preserved by the controller.
 */
class UpdateSectionRequest extends FormRequest
{
    /**
     * Field rules per section type, relative to the `content` key.
     *
     * @var array<string, array<string, array<int, string>>>
     */
    private const SCHEMAS = [
        'page_hero' => [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'string', 'max:2048'],
        ],
        'rich_text' => [
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'video_url' => ['nullable', 'url', 'max:2048'],
        ],
        'feature_grid' => [
            'title' => ['nullable', 'string', 'max:255'],
            'items' => ['present', 'array'],
            'items.*.icon' => ['nullable', 'string', 'max:2048'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.text' => ['required', 'string', 'max:2000'],
        ],
        'banner_carousel' => [
            'slides' => ['present', 'array'],
            'slides.*.image' => ['required', 'string', 'max:2048'],
            'slides.*.title' => ['nullable', 'string', 'max:255'],
            'slides.*.subtitle' => ['nullable', 'string', 'max:1000'],
            'slides.*.link_url' => ['nullable', 'url', 'max:2048'],
            'slides.*.link_label' => ['nullable', 'string', 'max:255'],
        ],
        'savings_simulator' => [
            'title' => ['required', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:1000'],
            'fuel_prices' => ['present', 'array'],
            'fuel_prices.*.fuel' => ['required', 'string', 'max:60'],
            'fuel_prices.*.label' => ['required', 'string', 'max:120'],
            'fuel_prices.*.price_per_unit' => ['required', 'numeric', 'min:0'],
            'fuel_prices.*.unit' => ['required', 'string', 'max:20'],
            'fuel_prices.*.equivalence_factor' => ['required', 'numeric', 'gt:0'],
        ],
        'clients_showcase' => [
            'title' => ['nullable', 'string', 'max:255'],
            'logos' => ['present', 'array'],
            'logos.*.image' => ['required', 'string', 'max:2048'],
            'logos.*.name' => ['required', 'string', 'max:255'],
            'logos.*.url' => ['nullable', 'url', 'max:2048'],
        ],
        'lead_capture_form' => [
            'title' => ['required', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:1000'],
            'submit_label' => ['required', 'string', 'max:60'],
        ],
        // `center` and `zoom` are locked: map layout, not marketing content.
        // The pins are not fields here — they come from the `stations` table.
        'stations_map' => [
            'title' => ['nullable', 'string', 'max:255'],
        ],
        'contact_info' => [
            'address' => ['required', 'string', 'max:500'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255'],
            'schedule' => ['nullable', 'string', 'max:500'],
            'additional_info' => ['nullable', 'string', 'max:2000'],
        ],
        'social_icons' => [
            'networks' => ['present', 'array'],
            'networks.*.network' => ['required', 'string', 'in:facebook,instagram,twitter,linkedin,youtube,tiktok'],
            'networks.*.url' => ['required', 'url', 'max:2048'],
        ],
        'whatsapp_button' => [
            'phone' => ['required', 'string', 'max:40'],
            'prefilled_message' => ['nullable', 'string', 'max:500'],
        ],
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        /** @var Section $section */
        $section = $this->route('section');

        if (! array_key_exists($section->type, self::SCHEMAS)) {
            throw ValidationException::withMessages([
                'content' => "El tipo de sección «{$section->type}» aún no es editable desde el panel.",
            ]);
        }

        $rules = ['content' => ['required', 'array']];

        foreach (self::SCHEMAS[$section->type] as $field => $fieldRules) {
            $rules["content.{$field}"] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['content' => 'contenido'];
    }
}
