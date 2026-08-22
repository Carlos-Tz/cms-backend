<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only agenda for the admin panel: appointments are only born on the
 * public site, so there is no store or update here. Deleting one cancels it.
 */
class AppointmentController extends Controller
{
    /**
     * List appointments, soonest first, optionally filtered by site slug.
     *
     * Ordered ascending, unlike the lead inbox: what matters in an agenda is
     * what is coming, not what was captured last.
     */
    public function index(Request $request): JsonResponse
    {
        $appointments = Appointment::query()
            ->with('station:id,name')
            ->when($request->string('site')->isNotEmpty(), fn ($query) => $query->whereHas(
                'site',
                fn ($site) => $site->where('slug', $request->string('site'))
            ))
            ->orderBy('date')
            ->orderBy('slot')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $appointments->items(),
            'meta' => [
                'page' => $appointments->currentPage(),
                'per_page' => $appointments->perPage(),
                'total' => $appointments->total(),
                'last_page' => $appointments->lastPage(),
            ],
        ]);
    }

    /**
     * The site and station are loaded so the panel can show where the
     * appointment is booked.
     */
    public function show(Appointment $appointment): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $appointment->load(['site', 'station']),
            'meta' => [],
        ]);
    }

    /**
     * Cancelling an appointment is deleting it: the MVP has no status column.
     */
    public function destroy(Appointment $appointment): JsonResponse
    {
        $appointment->delete();

        return response()->json([
            'status' => 'success',
            'data' => null,
            'meta' => ['message' => 'Cita cancelada correctamente'],
        ]);
    }
}
