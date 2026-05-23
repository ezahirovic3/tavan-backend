<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\SocialAuthRedirectController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\BrandSuggestionController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\ProductImageController;
use App\Http\Controllers\Api\UserAvatarController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SupportInquiryController;
use App\Http\Controllers\Api\TradeController;
use App\Http\Controllers\Api\UserAddressController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProductReportController;
use App\Http\Controllers\Api\UserBlockController;
use App\Http\Controllers\Api\UserReportController;
use App\Http\Controllers\Api\PushTokenController;
use App\Http\Controllers\Api\UserPreferenceController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\BlogPostController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\TrackingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ── Public ────────────────────────────────────────────────────────────────
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/social/google', [SocialAuthController::class, 'google']);
    Route::post('auth/social/apple', [SocialAuthController::class, 'apple']);
    Route::get('auth/social/google/redirect', [SocialAuthRedirectController::class, 'redirect'])->withoutMiddleware(\App\Http\Middleware\VerifyAppKey::class);
    Route::get('auth/social/google/callback', [SocialAuthRedirectController::class, 'callback'])->withoutMiddleware(\App\Http\Middleware\VerifyAppKey::class);

    Route::post('auth/email/verify', [AuthController::class, 'verifyEmail'])->middleware('throttle:5,10');
    Route::post('auth/email/resend', [AuthController::class, 'resendEmailVerification'])->middleware('throttle:5,10');

    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('auth/verify-reset-otp', [AuthController::class, 'verifyResetOtp']);
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('auth/phone/send-otp', [AuthController::class, 'sendPhoneOtp']);

    Route::get('brands', [BrandController::class, 'index']);

    // Blog (slugs must be declared before {slug} wildcard)
    Route::get('posts', [BlogPostController::class, 'index']);
    Route::get('posts/slugs', [BlogPostController::class, 'slugs']);
    Route::get('posts/{slug}', [BlogPostController::class, 'show']);

    // Products feed + detail — no auth required, but controller uses user() if present
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);

    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{username}', [UserController::class, 'show']);
    // Public: controller uses optional auth to restrict draft visibility to owner only
    Route::get('users/{username}/products', [UserController::class, 'products']);

    // Support — public so the landing page (unauthenticated) can submit inquiries
    Route::post('support', [SupportInquiryController::class, 'store']);

    // Announcements — public; guests see only target_group=all, no read tracking
    Route::get('announcements', [AnnouncementController::class, 'index']);

    // Tracking — public, gated by VerifyAppKey (applied to all /api/v1/ routes)
    Route::middleware('throttle:60,1')->group(function () {
        Route::post('tracking/share-view', [TrackingController::class, 'shareView']);
        Route::get('tracking/campaign/{id}', [TrackingController::class, 'campaign']);
        Route::post('tracking/campaign-event', [TrackingController::class, 'campaignEvent']);
    });

    // ── Authenticated ─────────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/change-password', [AuthController::class, 'changePassword']);
        Route::post('auth/phone/verify-otp', [AuthController::class, 'verifyPhoneOtp']);

        Route::get('users/me/blocks', [UserBlockController::class, 'index']);
        Route::post('users/{user}/block', [UserBlockController::class, 'store']);
        Route::delete('users/{user}/block', [UserBlockController::class, 'destroy']);
        Route::post('users/{user}/report', [UserReportController::class, 'store']);

        Route::patch('users/me', [UserController::class, 'update']);
        Route::delete('users/me', [UserController::class, 'destroy']);
        Route::delete('users/me/deletion', [UserController::class, 'cancelDeletion']);
        Route::post('users/me/avatar', [UserAvatarController::class, 'update']);
        Route::get('users/me/preferences', [UserPreferenceController::class, 'show']);
        Route::patch('users/me/preferences', [UserPreferenceController::class, 'update']);
        Route::get('users/me/notifications', [UserController::class, 'getNotificationPref']);
        Route::patch('users/me/notifications', [UserController::class, 'setNotificationPref']);

        Route::get('users/me/addresses', [UserAddressController::class, 'index']);
        Route::post('users/me/addresses', [UserAddressController::class, 'store']);
        Route::patch('users/me/addresses/{address}', [UserAddressController::class, 'update']);
        Route::delete('users/me/addresses/{address}', [UserAddressController::class, 'destroy']);

        Route::post('products', [ProductController::class, 'store']);
        Route::patch('products/{product}', [ProductController::class, 'update']);
        Route::delete('products/{product}', [ProductController::class, 'destroy']);
        Route::post('products/{product}/publish', [ProductController::class, 'publish']);
        Route::post('products/{product}/report', [ProductReportController::class, 'store']);
        Route::post('products/{product}/images', [ProductImageController::class, 'store']);
        Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy']);
        Route::patch('products/{product}/images/reorder', [ProductImageController::class, 'reorder']);

        Route::get('wishlist', [WishlistController::class, 'index']);
        Route::post('wishlist/{product}/toggle', [WishlistController::class, 'toggle']);
        Route::post('wishlist/{product}', [WishlistController::class, 'store']);
        Route::delete('wishlist/{product}', [WishlistController::class, 'destroy']);

        // Offers
        Route::post('offers', [OfferController::class, 'store']);
        Route::get('offers/{offer}', [OfferController::class, 'show']);
        Route::post('offers/{offer}/accept', [OfferController::class, 'accept']);
        Route::post('offers/{offer}/decline', [OfferController::class, 'decline']);
        Route::post('offers/{offer}/counter', [OfferController::class, 'counter']);

        // Trades
        Route::post('trades', [TradeController::class, 'store']);
        Route::get('trades/{trade}', [TradeController::class, 'show']);
        Route::post('trades/{trade}/accept', [TradeController::class, 'accept']);
        Route::post('trades/{trade}/decline', [TradeController::class, 'decline']);
        Route::post('trades/{trade}/counter', [TradeController::class, 'counter']);

        // Orders
        Route::get('orders', [OrderController::class, 'index']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::post('orders/{order}/accept', [OrderController::class, 'accept']);
        Route::post('orders/{order}/ship', [OrderController::class, 'ship']);
        Route::post('orders/{order}/deliver', [OrderController::class, 'deliver']);
        Route::post('orders/{order}/complete', [OrderController::class, 'complete']);
        Route::post('orders/{order}/decline', [OrderController::class, 'decline']);

        // Reviews
        Route::get('reviews/{review}', [ReviewController::class, 'show']);
        Route::post('orders/{order}/reviews', [ReviewController::class, 'store']);

        // Brand suggestions
        Route::post('brand-suggestions', [BrandSuggestionController::class, 'store']);

        // Push tokens
        Route::post('push-tokens', [PushTokenController::class, 'store']);
        Route::delete('push-tokens', [PushTokenController::class, 'destroy']);
        Route::post('push-tokens/badge/reset', [PushTokenController::class, 'resetBadge']);

        // Announcements (index is public; read-tracking routes require auth)
        Route::get('announcements/unread-count', [AnnouncementController::class, 'unreadCount']);
        Route::post('announcements/{announcement}/read', [AnnouncementController::class, 'markRead']);

        // Conversations
        Route::get('conversations', [ConversationController::class, 'index']);
        Route::get('conversations/unread', [ConversationController::class, 'unreadCount']);
        Route::post('conversations/support', [ConversationController::class, 'support']);
        Route::post('conversations', [ConversationController::class, 'store']);
        Route::get('conversations/{conversation}', [ConversationController::class, 'show']);
        Route::get('conversations/{conversation}/info', [ConversationController::class, 'info']);
        Route::post('conversations/{conversation}/messages', [ConversationController::class, 'sendMessage']);
        Route::post('conversations/{conversation}/read', [ConversationController::class, 'markRead']);

    });

    // ── Public reviews ────────────────────────────────────────────────────────
    Route::get('users/{username}/reviews', [ReviewController::class, 'userReviews']);

    // ── Broadcasting channel auth (mobile uses Bearer token, not web session) ─
    Route::post('broadcasting/auth', function (\Illuminate\Http\Request $request) {
        return \Illuminate\Support\Facades\Broadcast::auth($request);
    })->middleware('auth:sanctum');


});
