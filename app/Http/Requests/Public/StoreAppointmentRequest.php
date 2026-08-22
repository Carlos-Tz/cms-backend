<?php

namespace App\Http\Requests\Public;

use App\Models\Appointment;
use App\Models\Site;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Validates an inspection booked from the public site.
 *
 * `site_id` is never accepted: the site comes from the `{site}` segment of the
 * route. The station must belong to that site and be active, so a visitor
 * cannot book at another site's station by guessing an id.
 */
class StoreAppointmentRequest extends FormRequest
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
            'station_id' => [
                'required',
                'integer',
                Rule::exists('stations', 'id')
                    ->where('site_id', $site->id)
                    ->where('is_active', true),
            ],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', $this->notSunday(...)],
            'slot' => [
                'required',
                Rule::in(Appointment::SLOTS),
                // Readable 422 for a taken slot. The unique index on
                // (station_id, date, slot) is what actually prevents the race.
                Rule::unique('appointments', 'slot')
                    ->where('station_id', $this->integer('station_id'))
                    ->where('date', $this->string('date')->toString()),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'vehicle_plate' => ['required', 'string', 'max:20'],
            'vehicle_description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * The workshop opens Monday to Saturday.
     */
    private function notSunday(string $attribute, mixed $value, callable $fail): void
    {
        if (Carbon::parse((string) $value)->isSunday()) {
            $fail('No hay verificaciones en domingo. Elige un día de lunes a sábado.');
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'station_id' => 'estación',
            'date' => 'fecha',
            'slot' => 'horario',
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'vehicle_plate' => 'placas',
            'vehicle_description' => 'vehículo',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'station_id.exists' => 'La estación seleccionada no está disponible.',
            'date.after_or_equal' => 'La fecha ya pasó. Elige un día a partir de hoy.',
            'slot.in' => 'El horario seleccionado no existe.',
            'slot.unique' => 'Ese horario ya está ocupado. Elige otro.',
            'vehicle_plate.required' => 'Escribe las placas de tu vehículo.',
        ];
    }
}
