<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogPostResource;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;

class BlogPostController extends Controller
{
    public function index(): JsonResponse
    {
        $posts = BlogPost::published()->get();

        return response()->json(BlogPostResource::collection($posts)->resolve());
    }

    public function slugs(): JsonResponse
    {
        $slugs = BlogPost::published()->pluck('slug');

        return response()->json($slugs);
    }

    public function show(string $slug): JsonResponse
    {
        $post = BlogPost::published()->where('slug', $slug)->first();

        if (! $post) {
            return response()->json(['message' => 'Post nije pronađen.'], 404);
        }

        return response()->json((new BlogPostResource($post))->resolve());
    }
}
