<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ReorderImagesRequest;
use App\Http\Requests\Product\UploadImageRequest;
use App\Http\Resources\ProductImageResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductImageController extends Controller
{
    public function __construct(private readonly ImageService $images) {}

    public function store(UploadImageRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $image = $this->images->uploadProductImage($product, $request->file('image'));

        return response()->json(['data' => new ProductImageResource($image)], 201);
    }

    public function destroy(Request $request, Product $product, ProductImage $image): JsonResponse
    {
        $this->authorize('update', $product);
        abort_if($image->product_id !== $product->id, 404);

        $this->images->deleteProductImage($image);

        return response()->json(null, 204);
    }

    public function reorder(ReorderImagesRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $this->images->reorderProductImages($product, $request->ids);

        return response()->json([
            'data' => ProductImageResource::collection($product->images()->get()),
        ]);
    }
}
