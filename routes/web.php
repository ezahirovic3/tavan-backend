<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/health', function () {
    return response()->json([
        'ok'                 => true,
        'minVersion'         => config('app.min_version'),
        'recommendedVersion' => config('app.recommended_version'),
        'iosStoreUrl'        => config('app.ios_store_url'),
        'androidStoreUrl'    => config('app.android_store_url'),
    ]);
});
