<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;

class ProductReviewService
{
    public function __construct(
        private ConversationService $conversations,
        private PushNotificationService $push,
    ) {}

    /**
     * Approve a listing that is awaiting review: publish it, and — if the seller
     * is still gated behind manual review — lift that gate and let them know.
     *
     * The seller-gate side effect runs at most once per seller: a conditional
     * UPDATE flips the flag, so bulk-approving several of one seller's listings
     * only sends the "you're approved" message on the first.
     *
     * @return bool whether the seller's review gate was lifted by this call
     */
    public function approve(Product $product, User $reviewer): bool
    {
        if ($product->status !== 'pending_review') {
            return false;
        }

        $product->update(['status' => 'active']);

        $seller = $product->seller;

        if (! $seller) {
            return false;
        }

        $gateLifted = User::whereKey($seller->id)
            ->where('listings_require_review', true)
            ->update(['listings_require_review' => false]) > 0;

        if (! $gateLifted) {
            return false;
        }

        $conversation = $this->conversations->findOrCreateSupportConversation($seller->id);

        $this->conversations->sendSupportReply(
            $conversation,
            $reviewer,
            "Tvoji oglasi su pregledani i odobreni! 🎉\n\nOd sada svi tvoji novi oglasi idu direktno online — nema više čekanja na pregled.\n\nNapomena: Ako primimo prijave vezane za tvoj profil ili oglase, pregled može biti ponovo uključen. Hvala na razumijevanju i dobrodošao/la u Tavan zajednicu! 🩷",
        );

        $this->push->sendToUser(
            $seller->id,
            'Tavan Podrška',
            'Tvoji oglasi su odobreni! Od sada objavljuješ direktno online 🎉',
            ['type' => 'support_message', 'conversationId' => $conversation->id],
        );

        return true;
    }

    /**
     * Reject a listing under review: send it back to draft and message the
     * seller the reason in their support conversation.
     */
    public function reject(Product $product, User $reviewer, string $reason): void
    {
        $product->update(['status' => 'draft']);

        $conversation = $this->conversations->findOrCreateSupportConversation($product->seller_id);

        $this->conversations->sendSupportReply(
            $conversation,
            $reviewer,
            "Tvoj oglas \"{$product->title}\" je odbijen.\n\nRazlog: {$reason}",
        );

        $this->push->sendToUser(
            $product->seller_id,
            'Oglas odbijen',
            "Tvoj oglas \"{$product->title}\" je odbijen. Otvori poruke za detalje.",
            ['type' => 'support_message', 'conversationId' => $conversation->id],
        );
    }
}
