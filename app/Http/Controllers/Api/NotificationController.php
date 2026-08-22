<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\NotificationCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * The bell-icon activity feed — App\Notifications\UserActivityNotification
 * rows for the authenticated user. Mirrors AnnouncementController's shape
 * (index/unread-count/read), plus a bulk read-all.
 */
class NotificationController extends Controller
{
    /**
     * GET /notifications
     * Newest first, restricted to the event types this feed covers (see
     * NotificationCategory::LIST_TYPES) so a future unrelated use of the
     * `database` channel doesn't leak into this list.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->whereIn('data->type', NotificationCategory::LIST_TYPES)
            ->orderByDesc('created_at')
            ->paginate(30);

        $items = $notifications->map(fn (DatabaseNotification $n) => $this->toItem($n));

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'per_page'     => $notifications->perPage(),
                'total'        => $notifications->total(),
            ],
        ]);
    }

    /**
     * GET /notifications/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()
            ->unreadNotifications()
            ->whereIn('data->type', NotificationCategory::LIST_TYPES)
            ->count();

        return response()->json(['data' => ['count' => $count]]);
    }

    /**
     * POST /notifications/{notification}/read
     */
    public function markRead(Request $request, string $notification): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        return response()->json(['message' => 'Označeno kao pročitano.']);
    }

    /**
     * POST /notifications/read-all
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()
            ->unreadNotifications()
            ->whereIn('data->type', NotificationCategory::LIST_TYPES)
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Sve je označeno kao pročitano.']);
    }

    /**
     * DELETE /notifications/{notification}
     */
    public function destroy(Request $request, string $notification): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->delete();

        return response()->json(['message' => 'Obavještenje je uklonjeno.']);
    }

    private function toItem(DatabaseNotification $n): array
    {
        return [
            'id'        => $n->id,
            'type'      => $n->data['type'] ?? null,
            'title'     => $n->data['title'] ?? '',
            'body'      => $n->data['body'] ?? '',
            'meta'      => $n->data['meta'] ?? [],
            'is_read'   => $n->read_at !== null,
            'created_at' => $n->created_at?->toISOString(),
        ];
    }
}
