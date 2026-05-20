<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignEvent;
use App\Models\ShareView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function shareView(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entity_type' => ['required', 'in:product,profile'],
            'entity_id'   => ['required', 'string'],
            'platform'    => ['required', 'in:ios,android,desktop'],
            'outcome'     => ['required', 'in:app_opened,store_redirect,unknown'],
            'referrer'    => ['nullable', 'string', 'max:500'],
        ]);

        ShareView::create([
            'entity_type'       => $data['entity_type'],
            'entity_id'         => $data['entity_id'],
            'platform'          => $data['platform'],
            'outcome'           => $data['outcome'],
            'referrer'          => $data['referrer'] ?? null,
            'referrer_platform' => $this->parseReferrerPlatform($data['referrer'] ?? null),
        ]);

        return response()->json(['data' => ['recorded' => true]], 201);
    }

    private function parseReferrerPlatform(?string $referrer): ?string
    {
        if (! $referrer) return 'direct';

        $host = strtolower(parse_url($referrer, PHP_URL_HOST) ?? '');

        return match (true) {
            str_contains($host, 'instagram.com') => 'instagram',
            str_contains($host, 'facebook.com') || str_contains($host, 'fb.com') => 'facebook',
            str_contains($host, 'whatsapp.com') || str_contains($host, 'wa.me')  => 'whatsapp',
            str_contains($host, 'twitter.com') || str_contains($host, 'x.com')   => 'twitter',
            $host === ''                                                            => 'direct',
            default                                                                => 'other',
        };
    }

    public function campaign(string $id): JsonResponse
    {
        $campaign = Campaign::find($id);

        if (! $campaign || $campaign->status !== 'active') {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json(['data' => ['id' => $campaign->id, 'status' => $campaign->status]]);
    }

    public function campaignEvent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'campaign_id' => ['required', 'string', 'exists:campaigns,id'],
            'type'        => ['required', 'in:link_click,app_install'],
            'platform'    => ['nullable', 'in:ios,android,desktop'],
            'outcome'     => ['nullable', 'in:app_opened,store_redirect'],
        ]);

        $campaign = Campaign::find($data['campaign_id']);

        if ($campaign->status !== 'active') {
            return response()->json(['data' => ['recorded' => false]]);
        }

        CampaignEvent::create([
            'campaign_id' => $data['campaign_id'],
            'type'        => $data['type'],
            'platform'    => $data['platform'] ?? null,
            'outcome'     => $data['outcome'] ?? null,
        ]);

        return response()->json(['data' => ['recorded' => true]], 201);
    }
}
