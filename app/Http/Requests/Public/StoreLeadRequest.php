<?php

namespace App\Http\Requests\Public;

use App\Models\Lead;
use App\Models\Site;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a lead submitted from a public site form.
 *
 * `site_id` is never accepted here — the site comes from the `{site}` segment
 * of the route.
 *
 * The four core fields are columns of `leads`. Anything else a form declares in
 * the `fields[]` of its `lead_capture_form` section travels in `details`, and
 * **this class is what makes that declaration binding**: it reads the section
 * the lead came from and rejects any key that section did not declare. Without
 * it, `fields[]` would be a hint to the front-end that nothing enforces.
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
            'details' => ['nullable', 'array', $this->onlyDeclaredFields($site)],
            'details.*' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Reject any key the form of the `source` page did not declare.
     *
     * A lead with no `source` has no form to check against, so it accepts no
     * extras at all: closed by default, like an unknown section type.
     */
    private function onlyDeclaredFields(Site $site): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($site): void {
            $unknown = array_diff(array_keys((array) $value), $this->declaredExtraFields($site));

            if ($unknown !== []) {
                $fail('El formulario de esta página no acepta: '.implode(', ', $unknown).'.');
            }
        };
    }

    /**
     * The fields the source page's form declares beyond the four core ones.
     *
     * @return array<int, string>
     */
    private function declaredExtraFields(Site $site): array
    {
        $source = $this->string('source')->toString();

        if ($source === '') {
            return [];
        }

        $section = $site->sections()
            ->where('type', 'lead_capture_form')
            ->whereHas('page', fn ($page) => $page->where('slug', $source))
            ->first();

        return array_values(array_diff($section?->content['fields'] ?? [], Lead::CORE_FIELDS));
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'source' => 'origen',
            'details' => 'datos del formulario',
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
