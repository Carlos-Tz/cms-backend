<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Computes the savings of switching to natural gas. The calculation is code;
 * the admin only maintains the reference prices inside the `savings_simulator`
 * section.
 */
class SavingsSimulatorController extends Controller
{
    /**
     * Identifier of the fuel sold by the company — the baseline of every comparison.
     */
    private const BASE_FUEL = 'gas-natural';

    public function __invoke(Request $request, Site $site): JsonResponse
    {
        $input = $request->validate([
            'fuel' => ['required', 'string'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'page' => ['nullable', 'string'],
        ], [], ['fuel' => 'combustible', 'quantity' => 'cantidad', 'page' => 'página']);

        // A site can carry several savings_simulator sections (GNU has one on the
        // home page and one on /nuestros-servicios/gnc) and each keeps its own
        // reference prices. `page` picks which one; without it the caller gets
        // the site's first, which is the home page.
        $section = $site->sections()
            ->where('type', 'savings_simulator')
            ->when(
                $request->string('page')->isNotEmpty(),
                fn ($query) => $query->whereHas(
                    'page',
                    fn ($page) => $page->where('slug', $request->string('page'))
                ),
            )
            ->orderBy('page_id')
            ->firstOrFail();

        $prices = collect($section->content['fuel_prices'] ?? [])->keyBy('fuel');

        $base = $prices->get(self::BASE_FUEL);
        $current = $prices->get($input['fuel']);

        if (! $base) {
            abort(503, 'El precio del gas natural no está configurado.');
        }

        if (! $current || $input['fuel'] === self::BASE_FUEL) {
            throw ValidationException::withMessages([
                'fuel' => 'El combustible seleccionado no está disponible para comparar.',
            ]);
        }

        $quantity = (float) $input['quantity'];
        $currentCost = $quantity * (float) $current['price_per_unit'];
        $gasQuantity = $quantity * (float) $current['equivalence_factor'];
        $gasCost = $gasQuantity * (float) $base['price_per_unit'];
        $savings = $currentCost - $gasCost;

        return response()->json([
            'status' => 'success',
            'data' => [
                'current_fuel' => [
                    'fuel' => $current['fuel'],
                    'label' => $current['label'],
                    'quantity' => round($quantity, 2),
                    'unit' => $current['unit'],
                    'price_per_unit' => round((float) $current['price_per_unit'], 2),
                    'cost' => round($currentCost, 2),
                ],
                'natural_gas' => [
                    'quantity' => round($gasQuantity, 2),
                    'unit' => $base['unit'],
                    'price_per_unit' => round((float) $base['price_per_unit'], 2),
                    'cost' => round($gasCost, 2),
                ],
                'savings' => round($savings, 2),
                'savings_percent' => $currentCost > 0 ? round($savings / $currentCost * 100, 2) : 0.0,
            ],
            'meta' => ['site' => $site->slug],
        ]);
    }
}
