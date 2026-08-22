<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Site;
use App\Models\Station;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Inspection booking for the `service_scheduling` section.
 *
 * The opening hours are code, not content: three two-hour slots, Monday to
 * Saturday, one appointment each. `Appointment::SLOTS` is their single source
 * of truth and this endpoint is how the front-end learns them.
 */
class AppointmentController extends Controller
{
    /**
     * Free slots of one station on one date.
     *
     * A closed day — Sunday, or a date already past — returns an empty list
     * rather than a 422: the widget asks for a whole week at a time and must
     * not break on the day the workshop is shut.
     */
    public function availability(Request $request, Site $site): JsonResponse
    {
        $input = $request->validate([
            'station' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
        ], [], ['station' => 'estación', 'date' => 'fecha']);

        /** @var Station $station */
        $station = $site->stations()->active()->findOrFail($input['station']);

        $date = Carbon::parse($input['date'])->startOfDay();
        $isOpen = ! $date->isSunday() && ! $date->isBefore(now()->startOfDay());

        $taken = $isOpen
            ? $station->appointments()->where('date', $date->toDateString())->pluck('slot')->all()
            : [];

        return response()->json([
            'status' => 'success',
            'data' => [
                'date' => $date->toDateString(),
                'station' => ['id' => $station->id, 'name' => $station->name],
                'slots' => $isOpen
                    ? array_map(fn (string $slot) => [
                        'slot' => $slot,
                        'available' => ! in_array($slot, $taken, true),
                    ], Appointment::SLOTS)
                    : [],
            ],
            'meta' => ['site' => $site->slug],
        ]);
    }

    /**
     * Book a slot. The confirmed date, slot and station are returned: unlike a
     * lead, the visitor needs to see which appointment they got.
     */
    public function store(StoreAppointmentRequest $request, Site $site): JsonResponse
    {
        try {
            $appointment = $site->appointments()->create($request->validated());
        } catch (UniqueConstraintViolationException) {
            // Someone booked the same slot between the validation and the insert.
            throw ValidationException::withMessages([
                'slot' => 'Ese horario acaba de ocuparse. Elige otro.',
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'date' => $appointment->date,
                'slot' => $appointment->slot,
                'station' => $appointment->station->name,
            ],
            'meta' => ['message' => 'Tu cita quedó registrada. Te esperamos.'],
        ], 201);
    }
}
