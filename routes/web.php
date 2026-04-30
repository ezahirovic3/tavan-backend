<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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
