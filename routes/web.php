<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-broadcast/{conversationId}', function (string $conversationId) {
    $message = \App\Models\Message::whereHas('conversation', function ($q) use ($conversationId) {
        $q->where('id', $conversationId);
    })->latest()->first();

    if (!$message) {
        return response()->json(['error' => 'no message found', 'conversationId' => $conversationId], 404);
    }

    try {
        $broadcaster = app(\Illuminate\Broadcasting\BroadcastManager::class)->connection('reverb');
        $result = $broadcaster->broadcast(
            ['private-conversation.' . $conversationId],
            'message.new',
            ['test' => true, 'conversationId' => $conversationId]
        );
        return response()->json([
            'status' => 'ok',
            'channel' => 'private-conversation.' . $conversationId,
            'result' => $result,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'class' => get_class($e),
        ], 500);
    }
});

Route::get('/health', function () {
    return response()->json([
        'ok'                 => true,
        'minVersion'         => env('APP_MIN_VERSION', '0.0.1'),
        'recommendedVersion' => env('APP_RECOMMENDED_VERSION', '0.0.1'),
        'iosStoreUrl'        => env('APP_IOS_STORE_URL', 'https://apps.apple.com/app/tavan/id123456789'),
        'androidStoreUrl'    => env('APP_ANDROID_STORE_URL', 'https://play.google.com/store/apps/details?id=ba.tavan.app'),
    ]);
});
