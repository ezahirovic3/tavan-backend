<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::whereHas('wishlistItems', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with(['images', 'brand', 'seller'])
            ->latest()
            ->paginate(20);

        $products->getCollection()->each(fn ($p) => $p->is_wishlisted = true);

        return response()->json([
            'data' => ProductResource::collection($products->items()),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
            ],
        ]);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        if ($product->seller_id === $request->user()->id) {
            return response()->json([
                'message' => 'Ne možeš lajkovati vlastiti artikal.',
                'code'    => 'OWN_PRODUCT',
            ], 422);
        }

        $exists = WishlistItem::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'data'    => ['product_id' => $product->id, 'is_wishlisted' => true],
                'message' => 'Već je u listi želja.',
            ], 409);
        }

        WishlistItem::create([
            'user_id'    => $request->user()->id,
            'product_id' => $product->id,
        ]);

        $product->increment('likes');

        return response()->json([
            'data' => ['product_id' => $product->id, 'is_wishlisted' => true],
        ], 201);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $deleted = WishlistItem::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->delete();

        if ($deleted) {
            $product->decrement('likes');
        }

        return response()->json([
            'data' => ['product_id' => $product->id, 'is_wishlisted' => false],
        ]);
    }
}
