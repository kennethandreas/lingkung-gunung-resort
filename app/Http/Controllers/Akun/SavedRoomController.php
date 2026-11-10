<?php

namespace App\Http\Controllers\Akun;

use App\Http\Controllers\Controller;
use App\Models\UnitType;
use App\Services\CustomerAccountService;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Akomodasi tersimpan (wishlist). Tidak membuat booking dan tidak menahan stok. */
class SavedRoomController extends Controller
{
    public function index(Request $request, PricingService $pricing): View
    {
        $types = $request->user('customer')->savedUnitTypes()
            ->where('unit_types.is_active', true)
            ->with(['rates', 'media'])
            ->orderByPivot('created_at', 'desc')
            ->get()
            ->map(fn (UnitType $type) => [
                'model' => $type,
                'prices' => $pricing->displayPrices($type),
                'cover' => $type->getFirstMedia('photos'),
                'unit' => match ($type->pricing_unit->value) {
                    'person' => 'orang/malam',
                    'tent' => 'tenda/malam',
                    'plot' => 'kavling/malam',
                    default => 'kamar/malam',
                },
            ]);

        return view('akun.tersimpan', ['rows' => $types]);
    }

    public function toggle(Request $request, UnitType $unitType, CustomerAccountService $accounts): JsonResponse|RedirectResponse
    {
        abort_unless($unitType->is_active, 404);

        $saved = $accounts->toggleSaved($request->user('customer'), $unitType);
        $message = $saved
            ? "{$unitType->marketing_name} disimpan ke akun Anda."
            : "{$unitType->marketing_name} dihapus dari daftar tersimpan.";

        if ($request->expectsJson()) {
            return response()->json(['saved' => $saved, 'message' => $message]);
        }

        return back()->with('status', $message);
    }
}
