<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductReportController extends Controller
{
    /** POST /products/{product}/report */
    public function store(Request $request, Product $product): JsonResponse
    {
        abort_if($request->user()->id === $product->seller_id, 422, 'Ne možeš prijaviti vlastiti oglas.');

        $request->validate([
            'reason'      => ['required', 'in:counterfeit,prohibited,misleading,spam,other'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        ProductReport::create([
            'reporter_id' => $request->user()->id,
            'product_id'  => $product->id,
            'reason'      => $request->reason,
            'description' => $request->description,
        ]);

        return response()->json(['message' => 'Prijava je poslana.']);
    }
}
