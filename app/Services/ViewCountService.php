<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ViewCountService
{
    private const BOT_PATTERN = '/bot|crawler|spider|preview|whatsapp|facebookexternalhit|telegram|twitterbot|slackbot|discordbot|googlebot/i';

    public function incrementProductView(Request $request, Product $product): void
    {
        if ($this->isBot($request)) return;

        $authUser = $request->user();
        if ($authUser?->id === $product->seller_id) return;

        $viewerKey = $authUser ? $authUser->id : hash('xxh3', $request->ip());

        if (! Cache::add("view:product:{$product->id}:{$viewerKey}:" . now()->toDateString(), 1, now()->addDay())) {
            return;
        }

        DB::update('UPDATE products SET view_count = view_count + 1 WHERE id = ?', [$product->id]);
    }

    public function incrementProfileView(Request $request, User $user): void
    {
        if ($this->isBot($request)) return;

        $authUser = $request->user();
        if ($authUser?->id === $user->id) return;

        $viewerKey = $authUser ? $authUser->id : hash('xxh3', $request->ip());

        if (! Cache::add("view:profile:{$user->id}:{$viewerKey}:" . now()->toDateString(), 1, now()->addDay())) {
            return;
        }

        DB::update('UPDATE users SET profile_view_count = profile_view_count + 1 WHERE id = ?', [$user->id]);
    }

    private function isBot(Request $request): bool
    {
        $ua = $request->userAgent() ?? '';
        return (bool) preg_match(self::BOT_PATTERN, $ua);
    }
}
