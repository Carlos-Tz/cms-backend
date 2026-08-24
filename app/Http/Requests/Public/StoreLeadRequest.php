<?php

namespace App\Http\Requests\Public;

use App\Models\Site;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a lead submitted from a public site form.
 *
 * The fields mirror the locked `fields[]` of the `lead_capture_form` section:
 * anything else the front sends is dropped. `site_id` is never accepted here —
 * the site comes from the `{site}` segment of the route.
 *
 * `source` is the exception to that mirror: it is not a form field the visitor
 * fills, it is the slug of the page the form was rendered on.
 */
class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Site $site */
        $site = $this->route('site');

        return [
            // Which form produced the lead. Not a trust boundary like `site_id`
            // — a spoofed value only mislabels one row — but it is validated
            // against the site's published pages so the inbox stays meaningful.
            'source' => [
                'nullable',
                'string',
                Rule::exists('pages', 'slug')
                    ->where('site_id', $site->id)
                    ->where('is_published', true),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'source' => 'origen',
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'message' => 'mensaje',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Escribe tu nombre.',
            'email.required' => 'Escribe tu correo electrónico.',
            'email.email' => 'El correo electrónico no es válido.',
            'source.exists' => 'El origen del formulario no corresponde a una página de este sitio.',
        ];
    }
}
