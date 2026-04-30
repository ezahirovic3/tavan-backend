<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::active()
            ->roots()
            ->with(['children' => fn ($q) => $q->active()->with(['children' => fn ($q) => $q->active()])])
            ->get();

        return response()->json(['data' => CategoryResource::collection($categories)]);
    }
}
