<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreAddressRequest;
use App\Http\Resources\UserAddressResource;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()->orderByDesc('is_default')->get();

        return response()->json(['data' => UserAddressResource::collection($addresses)]);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (! empty($data['is_default'])) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address = $request->user()->addresses()->create($data);

        return response()->json(['data' => new UserAddressResource($address)], 201);
    }

    public function update(StoreAddressRequest $request, UserAddress $address): JsonResponse
    {
        $this->authorize('update', $address);

        $data = $request->validated();

        if (! empty($data['is_default'])) {
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($data);

        return response()->json(['data' => new UserAddressResource($address->fresh())]);
    }

    public function destroy(Request $request, UserAddress $address): JsonResponse
    {
        $this->authorize('delete', $address);

        $address->delete();

        return response()->json(null, 204);
    }
}
