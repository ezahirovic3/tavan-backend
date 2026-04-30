<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // This is a public route (no auth:sanctum middleware), but we still want to
        // resolve the user from a bearer token when present — for personalization and
        // wishlist flags. Auth::guard('sanctum')->user() does this without requiring auth.
        $authUser = $request->user() ?? \Illuminate\Support\Facades\Auth::guard('sanctum')->user();

        $query = Product::active()
            ->with(['images', 'brand', 'seller']);

        // ── Category filters ───────────────────────────────────────────────────
        if ($request->filled('root_category')) {
            $query->where('root_category', $request->root_category);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // subcategory accepts a single value OR an array (from multi-select filters)
        if ($request->filled('subcategory')) {
            $query->where('subcategory', $request->subcategory);
        } elseif ($request->filled('subcategories')) {
            $query->whereIn('subcategory', (array) $request->subcategories);
        }

        // ── Attribute filters (all accept single value or array) ──────────────
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }

        if ($request->filled('sizes')) {
            $query->whereIn('size', (array) $request->sizes);
        } elseif ($request->filled('size')) {
            $query->where('size', $request->size);
        }

        if ($request->filled('colors')) {
            $query->whereIn('color', (array) $request->colors);
        } elseif ($request->filled('color')) {
            $query->where('color', $request->color);
        }

        if ($request->filled('materials')) {
            $query->whereIn('material', (array) $request->materials);
        }

        // ── Brand filter ───────────────────────────────────────────────────────
        if ($request->filled('brands')) {
            $query->whereHas('brand', fn ($q) => $q->whereIn('name', (array) $request->brands));
        }

        // ── Location filter ────────────────────────────────────────────────────
        if ($request->filled('cities')) {
            $query->whereIn('location', (array) $request->cities);
        } elseif ($request->filled('location')) {
            $query->where('location', $request->location);
        }

        // ── Price range ────────────────────────────────────────────────────────
        // Frontend sends priceMin/priceMax → middleware converts to price_min/price_max
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }

        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        // ── Search ─────────────────────────────────────────────────────────────
        if ($request->filled('q')) {
            $query->where(fn ($q) => $q
                ->where('title', 'like', '%'.$request->q.'%')
                ->orWhere('description', 'like', '%'.$request->q.'%')
            );
        }

        // ── Sorting ────────────────────────────────────────────────────────────
        // Frontend sends sortBy → middleware converts to sort_by
        match ($request->input('sort_by', 'newest')) {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'oldest'     => $query->oldest(),
            default      => $query->latest(),
        };

        // Personalized feed — filter by the authenticated user's saved preferences.
        // personalized=true  → products matching ANY preference (size OR root_category OR city)
        // personalized=exclude → products matching NONE of the preferences ("Ostali artikli")
        $personalizedParam = $request->query('personalized');

        if ($personalizedParam && $authUser) {
            $prefs = $authUser->preference;

            if ($prefs) {
                $sizes = array_merge(
                    $prefs->top_sizes    ?? [],
                    $prefs->bottom_sizes ?? [],
                    $prefs->shoe_sizes   ?? []
                );
                $categories = $prefs->categories ?? [];
                $cities     = $prefs->cities     ?? [];

                $hasFilter = ! empty($sizes) || ! empty($categories) || ! empty($cities);

                if ($hasFilter) {
                    if ($personalizedParam === 'true' || $personalizedParam === '1') {
                        // Include products that match ANY preference (OR logic)
                        $query->where(function ($q) use ($sizes, $categories, $cities) {
                            if (! empty($sizes))      $q->orWhereIn('size', $sizes);
                            if (! empty($categories)) $q->orWhereIn('root_category', $categories);
                            if (! empty($cities))     $q->orWhereIn('location', $cities);
                        });
                    } elseif ($personalizedParam === 'exclude') {
                        // Exclude products that match ANY preference — the complement bucket
                        $query->whereNot(function ($q) use ($sizes, $categories, $cities) {
                            if (! empty($sizes))      $q->orWhereIn('size', $sizes);
                            if (! empty($categories)) $q->orWhereIn('root_category', $categories);
                            if (! empty($cities))     $q->orWhereIn('location', $cities);
                        });
                    }
                }
            }
        }

        $products = $query->paginate(20);

        // Attach is_wishlisted flag if authenticated
        if ($authUser) {
            $wishlistedIds = WishlistItem::where('user_id', $authUser->id)
                ->whereIn('product_id', $products->pluck('id'))
                ->pluck('product_id')
                ->flip();

            $products->each(fn ($p) => $p->is_wishlisted = $wishlistedIds->has($p->id));
        }

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

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $request->user()->products()->create($request->validated());

        return response()->json(['data' => new ProductResource($product->load('images', 'brand'))], 201);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $product->load(['images', 'brand', 'seller']);

        if ($request->user()) {
            $product->is_wishlisted = WishlistItem::where('user_id', $request->user()->id)
                ->where('product_id', $product->id)
                ->exists();
        }

        return response()->json(['data' => new ProductResource($product)]);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product->update($request->validated());

        return response()->json(['data' => new ProductResource($product->fresh()->load('images', 'brand'))]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->json(null, 204);
    }

    public function publish(Request $request, Product $product): JsonResponse
    {
        $this->authorize('publish', $product);

        abort_if($product->status !== 'draft', 422, 'Samo draft proizvodi mogu biti objavljeni.');

        $product->update(['status' => 'active']);

        return response()->json(['data' => new ProductResource($product->fresh()->load('images', 'brand'))]);
    }
}
