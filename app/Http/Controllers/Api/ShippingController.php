<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShippingZone;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    // Get all active shipping zones (public)
    public function zones()
    {
        $zones = ShippingZone::where('is_active', true)
            ->orderBy('delivery_charge')
            ->get(['id', 'city', 'region', 'delivery_charge', 'estimated_days']);

        return response()->json([
            'success' => true,
            'data' => $zones,
        ]);
    }

    // Calculate shipping charge for a city
    public function calculate(Request $request)
    {
        $request->validate([
            'city' => 'required|string',
        ]);

        $zone = ShippingZone::where('is_active', true)
            ->where('city', $request->city)
            ->first();

        if (!$zone) {
            // Default to "Other" zone
            $zone = ShippingZone::where('city', 'Other')->first();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'city' => $zone->city ?? $request->city,
                'delivery_charge' => $zone->delivery_charge ?? 350,
                'estimated_days' => $zone->estimated_days ?? 7,
                'message' => "Delivery in {$zone->estimated_days} days - Rs. {$zone->delivery_charge}",
            ]
        ]);
    }

    // Admin: manage shipping zones
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => ShippingZone::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'city' => 'required|string|unique:shipping_zones,city',
            'region' => 'nullable|string',
            'delivery_charge' => 'required|numeric|min:0',
            'estimated_days' => 'required|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $zone = ShippingZone::create($validated);
        return response()->json([
            'success' => true,
            'message' => 'Shipping zone created',
            'data' => $zone,
        ]);
    }

    public function update(Request $request, $id)
    {
        $zone = ShippingZone::findOrFail($id);
        $zone->update($request->validate([
            'city' => 'sometimes|string',
            'region' => 'nullable|string',
            'delivery_charge' => 'sometimes|numeric|min:0',
            'estimated_days' => 'sometimes|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Shipping zone updated',
            'data' => $zone,
        ]);
    }

    public function destroy($id)
    {
        ShippingZone::findOrFail($id)->delete();
        return response()->json([
            'success' => true,
            'message' => 'Shipping zone deleted',
        ]);
    }
}
