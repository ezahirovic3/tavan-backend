<?php

namespace App\Support;

/**
 * Maps a push/notification `type` (the same string used in every
 * PushNotificationService `data['type']` payload) to one of the 5 opt-out
 * categories a user can mute independently in Settings → Notifikacije.
 *
 * Single source of truth for both push-preference enforcement
 * (PushNotificationService) and the in-app notifications list icon/grouping
 * (NotificationController). Update this map, not the two call sites, when a
 * new push type is introduced.
 */
class NotificationCategory
{
    public const MESSAGES = 'messages';
    public const ORDERS = 'orders';
    public const ACTIVITY = 'activity';
    public const PRICE_DROPS = 'price_drops';
    public const ANNOUNCEMENTS = 'announcements';

    /**
     * @var array<string, string>
     */
    private const MAP = [
        'message'                   => self::MESSAGES,
        'offer'                     => self::MESSAGES,
        'trade'                     => self::MESSAGES,
        'support_message'           => self::MESSAGES,
        'order'                     => self::ORDERS,
        'review'                    => self::ACTIVITY,
        'vintage_approved'          => self::ACTIVITY,
        'designer_approved'         => self::ACTIVITY,
        'brand_suggestion_approved' => self::ACTIVITY,
        'brand_suggestion_rejected' => self::ACTIVITY,
        'price_drop'                => self::PRICE_DROPS,
        'announcement'              => self::ANNOUNCEMENTS,
    ];

    /**
     * The event `type` strings that get persisted to the in-app notifications
     * list (bell icon feed). Deliberately excludes messages/offers/trades
     * (chat-embedded, already have a working unread badge) and announcements
     * (already has its own inbox).
     *
     * @var string[]
     */
    public const LIST_TYPES = [
        'order',
        'review',
        'vintage_approved',
        'designer_approved',
        'brand_suggestion_approved',
        'brand_suggestion_rejected',
        'price_drop',
    ];

    /**
     * Resolve the mute category for a push/notification type.
     * Unmapped types fail open (null → caller should treat as "always send"),
     * so a future push type doesn't silently go unsent just because it
     * hasn't been categorized yet.
     */
    public static function forType(?string $type): ?string
    {
        return self::MAP[$type] ?? null;
    }

    /**
     * The user column that gates this category, e.g. 'notify_orders'.
     */
    public static function preferenceColumn(string $category): string
    {
        return "notify_{$category}";
    }
}
