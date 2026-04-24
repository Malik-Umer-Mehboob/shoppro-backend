<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShippingAddress;
use Illuminate\Http\Request;

class UserAddressController extends Controller
{
    /**
     * GET /api/user/addresses
     */
    public function index(Request $request)
    {
        $addresses = ShippingAddress::where('user_id', $request->user()->id)
            ->orderBy('is_default', 'desc')
            ->get();

        return response()->json($addresses);
    }

    /**
     * POST /api/user/addresses
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name'      => 'required|string|max:255',
            'phone'          => 'required|string|max:20',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city'           => 'required|string|max:100',
            'state'          => 'required|string|max:100',
            'country'        => 'required|string|max:100',
            'postal_code'    => 'required|string|max:20',
            'is_default'     => 'boolean',
        ]);

        $data['user_id'] = $request->user()->id;

        // If this is set as default, un-default all others
        if (!empty($data['is_default'])) {
            ShippingAddress::where('user_id', $data['user_id'])->update(['is_default' => false]);
        }

        // If user has no addresses, make this default automatically
        $count = ShippingAddress::where('user_id', $data['user_id'])->count();
        if ($count === 0) {
            $data['is_default'] = true;
        }

        $address = ShippingAddress::create($data);

        return response()->json(['message' => 'Address saved.', 'address' => $address], 201);
    }

    /**
     * PUT /api/user/addresses/{id}
     */
    public function update(Request $request, $id)
    {
        $address = ShippingAddress::where('user_id', $request->user()->id)->findOrFail($id);

        $data = $request->validate([
            'full_name'      => 'sometimes|string|max:255',
            'phone'          => 'sometimes|string|max:20',
            'address_line_1' => 'sometimes|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city'           => 'sometimes|string|max:100',
            'state'          => 'sometimes|string|max:100',
            'country'        => 'sometimes|string|max:100',
            'postal_code'    => 'sometimes|string|max:20',
            'is_default'     => 'boolean',
        ]);

        if (!empty($data['is_default'])) {
            ShippingAddress::where('user_id', $request->user()->id)->update(['is_default' => false]);
        }

        $address->update($data);

        return response()->json(['message' => 'Address updated.', 'address' => $address->fresh()]);
    }

    /**
     * DELETE /api/user/addresses/{id}
     */
    public function destroy(Request $request, $id)
    {
        $address = ShippingAddress::where('user_id', $request->user()->id)->findOrFail($id);
        $address->delete();

        return response()->json(['message' => 'Address deleted.']);
    }
}
