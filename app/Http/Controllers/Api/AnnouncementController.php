<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * GET /announcements
     * Returns all announcements relevant to the authenticated user, newest first.
     * Each entry includes an `is_read` flag.
     */
    public function index(Request $request): JsonResponse
    {
        $user    = $request->user();
        $readIds = $user
            ? AnnouncementRead::where('user_id', $user->id)->pluck('announcement_id')->flip()
            : collect();

        $announcements = Announcement::whereNotNull('sent_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(function ($q) use ($user) {
                $q->where('target_group', 'all');
                if ($user) {
                    $q->orWhere(fn ($s) => $s->where('target_group', 'verified')->where(fn () => $user->is_verified))
                      ->orWhere(fn ($s) => $s->where('target_group', 'city')->where('target_value', $user->location))
                      ->orWhere(fn ($s) => $s->where('target_group', 'listings_require_review')->where(fn () => $user->listings_require_review));
                }
            })
            ->orderByDesc('sent_at')
            ->paginate(30);

        $items = $announcements->map(fn ($a) => [
            'id'           => $a->id,
            'title'        => $a->title,
            'body'         => $a->body,
            'is_read'      => $readIds->has($a->id),
            'sent_at'      => $a->sent_at?->toISOString(),
        ]);

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $announcements->currentPage(),
                'last_page'    => $announcements->lastPage(),
                'per_page'     => $announcements->perPage(),
                'total'        => $announcements->total(),
            ],
        ]);
    }

    /**
     * POST /announcements/{announcement}/read
     * Mark an announcement as read for the authenticated user.
     */
    public function markRead(Request $request, Announcement $announcement): JsonResponse
    {
        AnnouncementRead::firstOrCreate([
            'announcement_id' => $announcement->id,
            'user_id'         => $request->user()->id,
        ]);

        return response()->json(['message' => 'Označeno kao pročitano.']);
    }

    /**
     * GET /announcements/unread-count
     * Returns the count of unread announcements for the authenticated user.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $user   = $request->user();

        $readIds = AnnouncementRead::where('user_id', $userId)->pluck('announcement_id');

        $count = Announcement::whereNotNull('sent_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->whereNotIn('id', $readIds)
            ->where(function ($q) use ($user) {
                $q->where('target_group', 'all')
                  ->orWhere(fn ($s) => $s->where('target_group', 'verified')->where(fn () => $user->is_verified))
                  ->orWhere(fn ($s) => $s->where('target_group', 'city')->where('target_value', $user->location))
                  ->orWhere(fn ($s) => $s->where('target_group', 'listings_require_review')->where(fn () => $user->listings_require_review));
            })
            ->count();

        return response()->json(['data' => ['count' => $count]]);
    }
}
