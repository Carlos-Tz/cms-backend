<?php

namespace App\Http\Requests\Admin;

use App\Models\Station;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a station on both create and update.
 *
 * Update takes the full payload (PUT semantics) so there is no separate request
 * class. `site_id` is only accepted on create: a station never moves between
 * sites, same rule as `BlogPostRequest`.
 */
class StationRequest extends FormRequest
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
        return [
            'site_id' => $this->station() === null
                ? ['required', 'integer', 'exists:sites,id']
                : ['prohibited'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'schedule' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'site_id' => 'sitio',
            'name' => 'nombre',
            'address' => 'dirección',
            'latitude' => 'latitud',
            'longitude' => 'longitud',
            'phone' => 'teléfono',
            'schedule' => 'horario',
            'is_active' => 'estado',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'site_id.prohibited' => 'Una estación no puede cambiar de sitio.',
            'latitude.between' => 'La latitud debe estar entre -90 y 90.',
            'longitude.between' => 'La longitud debe estar entre -180 y 180.',
        ];
    }

    /**
     * The station being updated, or null when creating.
     */
    private function station(): ?Station
    {
        /** @var Station|null $station */
        $station = $this->route('station');

        return $station;
    }
}
