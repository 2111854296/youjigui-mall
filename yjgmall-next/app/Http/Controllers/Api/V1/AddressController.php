<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        $items = $request->user()->addresses()->orderByDesc('is_default')->latest()->get();

        return response()->json([
            'data' => $items->map(fn (Address $a) => $this->transform($a)),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if (! empty($data['is_default'])) {
            $request->user()->addresses()->update(['is_default' => false]);
        }
        $item = $request->user()->addresses()->create($data);

        return response()->json(['data' => $this->transform($item)], 201);
    }

    public function update(Request $request, Address $address)
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $data = $this->validated($request);
        if (! empty($data['is_default'])) {
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }
        $address->update($data);

        return response()->json(['data' => $this->transform($address->fresh())]);
    }

    public function destroy(Request $request, Address $address)
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $address->delete();

        return response()->json(['message' => 'ok']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:50',
            'phone' => 'required|string|max:20',
            'province' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:50',
            'district' => 'nullable|string|max:50',
            'address' => 'required|string|max:255',
            'is_default' => 'boolean',
        ]);
    }

    private function transform(Address $a): array
    {
        return [
            'id' => $a->id,
            'name' => $a->name,
            'phone' => $a->phone,
            'province' => $a->province,
            'city' => $a->city,
            'district' => $a->district,
            'address' => $a->address,
            'is_default' => (bool) $a->is_default,
            'full' => trim(($a->province ?? '').($a->city ?? '').($a->district ?? '').$a->address),
        ];
    }
}
