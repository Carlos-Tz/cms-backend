<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\Station;
use Illuminate\Http\JsonResponse;

class StationController extends Controller
{
    /**
     * List the active stations of the given site, for the `stations_map` section.
     *
     * Unpaginated on purpose: a map draws every pin at once, and a marketing
     * site has tens of stations, not thousands.
     */
    public function index(Site $site): JsonResponse
    {
        $stations = $site->stations()->active()->orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $stations->map($this->present(...))->all(),
            'meta' => ['site' => $site->slug, 'total' => $stations->count()],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Station $station): array
    {
        return [
            'name' => $station->name,
            'address' => $station->address,
            'latitude' => $station->latitude,
            'longitude' => $station->longitude,
            'phone' => $station->phone,
            'schedule' => $station->schedule,
        ];
    }
}
