<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UploadAvatarRequest;
use App\Http\Resources\UserResource;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;

class UserAvatarController extends Controller
{
    public function __construct(private readonly ImageService $images) {}

    public function update(UploadAvatarRequest $request): JsonResponse
    {
        $this->images->uploadAvatar($request->user(), $request->file('avatar'));

        return response()->json(['data' => new UserResource($request->user()->fresh())]);
    }
}
