<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\WishlistItem;
use App\Services\ViewCountService;
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

        // Hide products from users in a block relationship with the viewer
        if ($authUser) {
            $blockedIds = $authUser->blockedUserIds();
            if (! empty($blockedIds)) {
                $query->whereNotIn('seller_id', $blockedIds);
            }
        }

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
        // personalized=true    → products matching preferences
        // personalized=exclude → products matching NONE of the preferences ("Ostali artikli")
        //
        // City is AND-ed with sizes/categories when both are set, so it narrows results
        // to local items rather than independently pulling in all items from that city.
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
                $brands     = $prefs->brands     ?? [];

                // Parse subcategory preference keys like "men-tops" → {root, category} pairs.
                // Keys are always "{rootId}-{categoryKey}" with no hyphens in either segment.
                $subcategoryPairs = collect($prefs->subcategories ?? [])
                    ->map(function (string $key) {
                        $dash = strpos($key, '-');
                        return $dash !== false
                            ? ['root' => substr($key, 0, $dash), 'category' => substr($key, $dash + 1)]
                            : null;
                    })
                    ->filter()
                    ->values();

                $hasSizesOrCategories = ! empty($sizes) || $subcategoryPairs->isNotEmpty() || ! empty($categories);
                $hasBrands            = ! empty($brands);
                $hasCities            = ! empty($cities);
                $hasFilter            = $hasSizesOrCategories || $hasBrands || $hasCities;

                // Closure that applies the size + category + brand OR block.
                $applyPreferences = function ($q) use ($sizes, $categories, $subcategoryPairs, $brands) {
                    if (! empty($sizes)) $q->orWhereIn('size', $sizes);

                    if ($subcategoryPairs->isNotEmpty()) {
                        $q->orWhere(function ($sq) use ($subcategoryPairs) {
                            foreach ($subcategoryPairs as $pair) {
                                $sq->orWhere(function ($pq) use ($pair) {
                                    $pq->where('root_category', $pair['root'])
                                       ->where('category', $pair['category']);
                                });
                            }
                        });
                    } elseif (! empty($categories)) {
                        $q->orWhereIn('root_category', $categories);
                    }

                    if (! empty($brands)) {
                        $q->orWhereHas('brand', fn ($bq) => $bq->whereIn('id', $brands));
                    }
                };

                // Build the match condition depending on which preference types are set.
                // When sizes/categories/brands AND cities are both set, city is AND-ed so it
                // restricts to local matches rather than independently including all city items.
                $hasSizesOrCategoriesOrBrands = $hasSizesOrCategories || $hasBrands;

                $applyMatch = function ($q) use ($hasSizesOrCategoriesOrBrands, $hasCities, $cities, $applyPreferences) {
                    if ($hasSizesOrCategoriesOrBrands && $hasCities) {
                        $q->where($applyPreferences)->whereIn('location', $cities);
                    } elseif ($hasSizesOrCategoriesOrBrands) {
                        $q->where($applyPreferences);
                    } else {
                        $q->whereIn('location', $cities);
                    }
                };

                if ($hasFilter) {
                    if ($personalizedParam === 'true' || $personalizedParam === '1') {
                        $query->where($applyMatch);
                    } elseif ($personalizedParam === 'exclude') {
                        $query->whereNot($applyMatch);
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
        $seller = $request->user();
        $data   = $request->validated();

        // If the client did not explicitly save as draft, determine the correct
        // published status from the seller's trust level:
        //   - listings_require_review = true  → pending_review (awaits admin approval)
        //   - listings_require_review = false → active (trusted seller, goes live immediately)
        if (($data['status'] ?? null) !== 'draft') {
            $data['status'] = $seller->listings_require_review ? 'pending_review' : 'active';
        }

        $product = $seller->products()->create($data);

        return response()->json(['data' => new ProductResource($product->load('images', 'brand'))], 201);
    }

    public function show(Request $request, Product $product, ViewCountService $viewCount): JsonResponse
    {
        $viewCount->incrementProductView($request, $product);

        $product->load(['images', 'brand', 'seller']);

        $authUser = $request->user() ?? \Illuminate\Support\Facades\Auth::guard('sanctum')->user();

        if ($authUser) {
            $product->is_wishlisted = WishlistItem::where('user_id', $authUser->id)
                ->where('product_id', $product->id)
                ->exists();
        }

        $sellerProducts = Product::where('seller_id', $product->seller_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            ->with('images')
            ->latest()
            ->take(5)
            ->get();

        $similarProducts = Product::where('category', $product->category)
            ->where('root_category', $product->root_category)
            ->where('id', '!=', $product->id)
            ->where('seller_id', '!=', $product->seller_id)
            ->where('status', 'active')
            ->with('images')
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'data'            => new ProductResource($product),
            'sellerProducts'  => ProductResource::collection($sellerProducts),
            'similarProducts' => ProductResource::collection($similarProducts),
        ]);
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

        abort_if(
            ! in_array($product->status, ['draft', 'pending_review']),
            422,
            'Samo draft ili pending_review proizvodi mogu biti objavljeni.'
        );

        $product->update(['status' => 'active']);

        return response()->json(['data' => new ProductResource($product->fresh()->load('images', 'brand'))]);
    }
}
