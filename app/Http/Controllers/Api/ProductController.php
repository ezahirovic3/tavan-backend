<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use App\Services\ProductSearchService;
use App\Services\ViewCountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // This is a public route (no auth:sanctum middleware), but we still want to
        // resolve the user from a bearer token when present — for personalization and
        // wishlist flags. Auth::guard('sanctum')->user() does this without requiring auth.
        $authUser = $request->user() ?? \Illuminate\Support\Facades\Auth::guard('sanctum')->user();

        // Pin the resolved user onto the request so ProductResource's own
        // $request->user() ?? Auth::guard('sanctum')->user() fallback (called
        // once per product in the collection) short-circuits instead of
        // re-resolving the sanctum token from cache + DB for every item.
        $request->setUserResolver(fn () => $authUser);

        $query = Product::active()
            ->with(['images', 'brand', 'seller']);

        // Hide products from users in a block relationship with the viewer
        if ($authUser) {
            $blockedIds = $authUser->blockedUserIds();
            if (! empty($blockedIds)) {
                $query->whereNotIn('seller_id', $blockedIds);
            }
        }

        // Hide products from banned sellers
        $query->whereDoesntHave('seller', fn ($q) => $q->where('banned_until', '>', now()));

        // Category, attribute, brand, location, price, and sort filters — shared
        // with UserController::products() via Product::scopeApplyFilters().
        $query->applyFilters($request);

        // ── Badge filters (standalone — also applied via personalized prefs) ─────
        if ($request->boolean('vintage_only')) {
            $query->where('vintage_status', 'approved');
        }
        if ($request->boolean('designer_only')) {
            $query->where('designer_status', 'approved');
        }

        // ── Search ─────────────────────────────────────────────────────────────
        // Multi-word queries match token-by-token: every token must hit somewhere
        // (AND across tokens, OR within a token's synonym expansions), so
        // "zara haljina" means brand/title "zara" AND the dress word family —
        // not the exact phrase "zara haljina" in a title.
        if ($request->filled('q')) {
            $tokens    = ProductSearchService::tokenize($request->q);
            $multiWord = count($tokens) > 1;

            // $includeCategoryFallback gates the category-level OR-branch below.
            // Kept as a closure parameter (rather than always on) so it can be
            // tried both ways: precise-only first, category-broadened only if
            // that comes back empty. See the fallback comment further down.
            $applyTokenMatches = function ($targetQuery, bool $includeCategoryFallback) use ($tokens, $multiWord) {
                foreach ($tokens as $token) {
                    $terms          = ProductSearchService::expandTerms($token, stemFallback: $multiWord);
                    $categoryIntent = $includeCategoryFallback
                        ? ProductSearchService::detectCategoryIntent($token)
                        : null;
                    $rootIntent  = ProductSearchService::detectRootIntent($token);
                    $styleIntent = ProductSearchService::detectStyleIntent($token);

                    $targetQuery->where(function ($q) use ($terms, $categoryIntent, $rootIntent, $styleIntent, $token) {
                        // Text match across title, description, subcategory, and brand name.
                        // Subcategory is included so a listing titled "Plave pantalone" but
                        // tagged subcategory="Farmerke" still surfaces when searching "farmerke" —
                        // and, since synonym groups are per-item-type (majica ≠ bluza ≠ košulja),
                        // this alone already keeps "majica" out of blouses/shirts/hoodies.
                        // Title and brand comparisons ignore apostrophes so "levis"
                        // matches "Levi's" (and the curly ’ variant).
                        foreach ($terms as $term) {
                            $stripped = ProductSearchService::stripApostrophes($term);

                            $q->orWhereRaw("REPLACE(REPLACE(title, '''', ''), '’', '') LIKE ?", ['%'.$stripped.'%'])
                              ->orWhere('description', 'like', '%'.$term.'%')
                              ->orWhere('subcategory', 'like', '%'.$term.'%')
                              ->orWhereHas('brand', fn ($b) => $b->whereRaw("REPLACE(REPLACE(name, '''', ''), '’', '') LIKE ?", ['%'.$stripped.'%']));
                        }

                        // Bare size tokens ("haljina xl", "new balance 38") match the
                        // size column directly — collation makes "xl" equal "XL".
                        $q->orWhere('size', $token);

                        // Category-level intent: "hlače" → bottoms, "patike" → shoes, etc.
                        // Fallback only (see caller) — every parent category groups several
                        // distinct item types (bottoms = hlače + suknje + šorc + trenerke),
                        // so applying this unconditionally would let all of them swamp a
                        // specific search instead of just catching the rare listing whose
                        // subcategory/title genuinely doesn't contain the searched word.
                        if ($categoryIntent) {
                            $q->orWhere('category', $categoryIntent);
                        }

                        // Gender intent: "muske majice" → men's section
                        if ($rootIntent) {
                            $q->orWhere('root_category', $rootIntent);
                        }

                        // Style intent: "goth", "y2k", "pokrivene" → tagged listings.
                        // "vintage"/"retro" additionally surface verified-vintage items —
                        // searchers don't care about our badge-vs-style distinction.
                        if ($styleIntent) {
                            $q->orWhereJsonContains('styles', $styleIntent);

                            if ($styleIntent === 'retro') {
                                $q->orWhere('vintage_status', 'approved');
                            }
                        }
                    });
                }
            };

            // Try the precise match (title/subcategory/brand/size/style/gender,
            // no category broadening) against a clone first. Only fall back to
            // including the whole parent category if that precise match finds
            // nothing at all — otherwise a handful of true "majica" results
            // would still get buried under every other top.
            $precise = (clone $query);
            $applyTokenMatches($precise, false);

            $applyTokenMatches($query, ! $precise->exists());
        }

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
                $topSizes    = $prefs->top_sizes    ?? [];
                $bottomSizes = $prefs->bottom_sizes ?? [];
                $shoeSizes   = $prefs->shoe_sizes   ?? [];
                $categories  = $prefs->categories   ?? [];
                $cities      = $prefs->cities       ?? [];

                // brands/styles are read but deliberately NOT used to filter the
                // personalized="true" match below — see note on $applyPreferences.

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

                $hasSizesOrCategories = ! empty($topSizes) || ! empty($bottomSizes) || ! empty($shoeSizes)
                    || $subcategoryPairs->isNotEmpty() || ! empty($categories);
                $hasCities            = ! empty($cities);
                $hasFilter            = $hasSizesOrCategories || $hasCities;
                $hasVintageOnly       = $prefs->vintage_only  ?? false;
                $hasDesignerOnly      = $prefs->designer_only ?? false;
                $hasBadgeFilter       = $hasVintageOnly || $hasDesignerOnly;

                // Closure that applies the size + category match. A product must satisfy
                // every facet the user configured (gender AND size), with OR only *within*
                // a facet (any of the selected sizes, any of the selected subcategories).
                // Previously these facets were OR'd against each other, so e.g. any item in
                // the right size — regardless of gender — would match; that's what let
                // menswear leak into a "women" + size feed.
                //
                // brands/styles are intentionally excluded from this hard match. Unlike
                // size/category — which every active listing has, enforced by the app's
                // publish flow — `styles` is a sparse, fully optional tag (few listings are
                // tagged), and AND'ing it in alongside size+category could easily zero out
                // the feed for anyone with a style preference set. Revisit once tag coverage
                // is high enough, and prefer using them as a ranking boost rather than a
                // hard filter that can empty the feed.
                //
                // Sizes are NOT a single flat scale — top/bottom/shoe preferences each use
                // their own numbering (mirrors PREFERENCE_SIZES in the mobile app's
                // constants/sizes.js), and those scales overlap numerically (bottoms and
                // shoes both run through the 34-46 range). A product's `size` value only
                // means something once you know which of the three scales it was picked
                // from — which is exactly what its `category` tells us. So each size
                // preference is scoped to the product categories that use that scale,
                // instead of matching `size` against one merged list regardless of category
                // — that's what let a shoe-size preference of 42 pull in a size-42 pair of
                // jeans, which has nothing to do with shoes.
                $apparelSections = ['tops', 'jackets', 'dresses', 'occasion', 'swimwear'];

                $applyPreferences = function ($q) use ($topSizes, $bottomSizes, $shoeSizes, $apparelSections, $categories, $subcategoryPairs) {
                    if (! empty($topSizes) || ! empty($bottomSizes) || ! empty($shoeSizes)) {
                        $q->where(function ($sq) use ($topSizes, $bottomSizes, $shoeSizes, $apparelSections) {
                            if (! empty($topSizes)) {
                                $sq->orWhere(fn ($tq) => $tq->whereIn('category', $apparelSections)->whereIn('size', $topSizes));
                            }
                            if (! empty($bottomSizes)) {
                                $sq->orWhere(fn ($bq) => $bq->where('category', 'bottoms')->whereIn('size', $bottomSizes));
                            }
                            if (! empty($shoeSizes)) {
                                $sq->orWhere(fn ($shq) => $shq->where('category', 'shoes')->whereIn('size', $shoeSizes));
                            }
                        });
                    }

                    if ($subcategoryPairs->isNotEmpty()) {
                        $q->where(function ($sq) use ($subcategoryPairs) {
                            foreach ($subcategoryPairs as $pair) {
                                $sq->orWhere(function ($pq) use ($pair) {
                                    $pq->where('root_category', $pair['root'])
                                       ->where('category', $pair['category']);
                                });
                            }
                        });
                    } elseif (! empty($categories)) {
                        $q->whereIn('root_category', $categories);
                    }
                };

                // Build the match condition depending on which preference types are set.
                // When sizes/categories AND cities are both set, city is AND-ed so it
                // restricts to local matches rather than independently including all city items.
                $applyMatch = function ($q) use ($hasSizesOrCategories, $hasCities, $cities, $applyPreferences) {
                    if ($hasSizesOrCategories && $hasCities) {
                        $q->where($applyPreferences)->whereIn('location', $cities);
                    } elseif ($hasSizesOrCategories) {
                        $q->where($applyPreferences);
                    } else {
                        $q->whereIn('location', $cities);
                    }
                };

                // Badge filter (vintage OR designer, or both).
                // Included  → at least one enabled badge approved
                // Excluded  → no enabled badge approved (AND between absent badges)
                if ($hasFilter || $hasBadgeFilter) {
                    if ($personalizedParam === 'true' || $personalizedParam === '1') {
                        if ($hasFilter) {
                            $query->where($applyMatch);
                        }
                        if ($hasBadgeFilter) {
                            if ($hasVintageOnly && $hasDesignerOnly) {
                                $query->where(fn ($q) => $q
                                    ->where('vintage_status', 'approved')
                                    ->orWhere('designer_status', 'approved')
                                );
                            } elseif ($hasVintageOnly) {
                                $query->where('vintage_status', 'approved');
                            } else {
                                $query->where('designer_status', 'approved');
                            }
                        }
                    } elseif ($personalizedParam === 'exclude') {
                        if ($hasBadgeFilter) {
                            if ($hasVintageOnly) {
                                $query->where(fn ($q) => $q
                                    ->whereNull('vintage_status')
                                    ->orWhere('vintage_status', '!=', 'approved')
                                );
                            }
                            if ($hasDesignerOnly) {
                                $query->where(fn ($q) => $q
                                    ->whereNull('designer_status')
                                    ->orWhere('designer_status', '!=', 'approved')
                                );
                            }
                        } elseif ($hasFilter) {
                            $query->whereNot($applyMatch);
                        }
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

        $data = array_merge($data, $this->autoDesignerFields($seller));

        $product = $seller->products()->create($data);

        return response()->json(['data' => new ProductResource($product->load('images', 'brand'))], 201);
    }

    /**
     * Designer makers (own original pieces, no brand to verify) get every
     * listing auto-tagged as designer at creation — no per-listing application.
     */
    private function autoDesignerFields(User $seller): array
    {
        if (! $seller->is_designer_maker) {
            return [];
        }

        return [
            'designer_status'      => 'approved',
            'designer_brand'       => $seller->name,
            'designer_reviewed_by' => $seller->id,
            'designer_reviewed_at' => now(),
        ];
    }

    public function show(Request $request, Product $product, ViewCountService $viewCount): JsonResponse
    {
        $product->load(['images', 'brand', 'seller']);

        // Hide products whose seller has requested deletion
        if ($product->seller?->deletion_requested_at) {
            abort(404);
        }

        $viewCount->incrementProductView($request, $product);

        $authUser = $request->user() ?? \Illuminate\Support\Facades\Auth::guard('sanctum')->user();

        // See index() — pins the resolved user so ProductResource's per-item
        // fallback doesn't re-resolve the sanctum token for every product
        // rendered below (main product + sellerProducts + similarProducts).
        $request->setUserResolver(fn () => $authUser);

        if ($authUser) {
            $product->is_wishlisted = WishlistItem::where('user_id', $authUser->id)
                ->where('product_id', $product->id)
                ->exists();
        }

        $sellerProducts = Product::active()
            ->where('seller_id', $product->seller_id)
            ->where('id', '!=', $product->id)
            ->with('images')
            ->latest()
            ->take(5)
            ->get();

        $similarProducts = Product::active()
            ->where('category', $product->category)
            ->where('root_category', $product->root_category)
            ->where('id', '!=', $product->id)
            ->where('seller_id', '!=', $product->seller_id)
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

    public function applyVintage(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        abort_if(
            in_array($product->vintage_status, ['pending', 'approved']),
            422,
            'Vintage zahtjev je već poslan ili odobren.'
        );

        $data = $request->validate([
            'era'        => ['required', Rule::in(['50s', '60s', '70s', '80s', '90s', 'y2k'])],
            'notes'      => ['required', 'string', 'max:1000'],
            'provenance' => ['nullable', 'string', 'max:500'],
        ]);

        $seller          = $request->user();
        $vintageStatus   = $seller->is_vintage_seller ? 'approved' : 'pending';

        $product->update([
            'vintage_status'    => $vintageStatus,
            'vintage_era'       => $data['era'],
            'vintage_notes'     => $data['notes'],
            'vintage_provenance'=> $data['provenance'] ?? null,
            'vintage_reject_reason'  => null,
            'vintage_reviewed_by'    => $seller->is_vintage_seller ? $seller->id : null,
            'vintage_reviewed_at'    => $seller->is_vintage_seller ? now() : null,
        ]);

        return response()->json(['data' => new ProductResource($product->fresh()->load('images', 'brand'))]);
    }

    public function applyDesigner(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        abort_if(
            in_array($product->designer_status, ['pending', 'approved']),
            422,
            'Designer zahtjev je već poslan ili odobren.'
        );

        $data = $request->validate([
            'brand' => ['required', 'string', 'max:200'],
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        $seller         = $request->user();
        $designerStatus = $seller->is_designer_reseller ? 'approved' : 'pending';

        $product->update([
            'designer_status'        => $designerStatus,
            'designer_brand'         => $data['brand'],
            'designer_notes'         => $data['notes'],
            'designer_reject_reason' => null,
            'designer_reviewed_by'   => $seller->is_designer_reseller ? $seller->id : null,
            'designer_reviewed_at'   => $seller->is_designer_reseller ? now() : null,
        ]);

        return response()->json(['data' => new ProductResource($product->fresh()->load('images', 'brand'))]);
    }

    public function publish(Request $request, Product $product): JsonResponse
    {
        $this->authorize('publish', $product);

        abort_if(
            ! in_array($product->status, ['draft', 'pending_review']),
            422,
            'Samo draft ili pending_review proizvodi mogu biti objavljeni.'
        );

        $missing = collect([
            'title'         => $product->title,
            'price'         => $product->price,
            'root_category' => $product->root_category,
            'condition'     => $product->condition,
            'location'      => $product->location,
        ])->filter(fn ($v) => $v === null || $v === '')->keys();

        abort_if(
            $missing->isNotEmpty(),
            422,
            'Oglas nije kompletan. Nedostaju polja: ' . $missing->join(', ') . '.'
        );

        abort_if(
            $product->images()->doesntExist(),
            422,
            'Oglas mora imati barem jednu fotografiju.'
        );

        $seller = $product->seller;
        $status = $seller->listings_require_review ? 'pending_review' : 'active';

        $product->update(['status' => $status]);

        return response()->json(['data' => new ProductResource($product->fresh()->load('images', 'brand'))]);
    }
}
