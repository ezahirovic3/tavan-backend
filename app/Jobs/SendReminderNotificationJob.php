<?php

namespace App\Jobs;

use App\Models\Offer;
use App\Models\Order;
use App\Models\Trade;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendReminderNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $modelType,
        private readonly string $modelId,
        private readonly string $reminderType,
    ) {}

    public function handle(PushNotificationService $push): void
    {
        match ($this->modelType) {
            'order' => $this->handleOrder($push),
            'offer' => $this->handleOffer($push),
            'trade' => $this->handleTrade($push),
            default => null,
        };
    }

    private function handleOrder(PushNotificationService $push): void
    {
        $order = Order::find($this->modelId);
        if (! $order) {
            return;
        }

        match ($this->reminderType) {
            'pending_seller'       => $this->orderPendingSeller($push, $order),
            'accepted_seller'      => $this->orderAcceptedSeller($push, $order),
            'accepted_buyer_pickup'=> $this->orderAcceptedBuyerPickup($push, $order),
            'shipped_buyer'        => $this->orderShippedBuyer($push, $order),
            default                => null,
        };
    }

    private function orderPendingSeller(PushNotificationService $push, Order $order): void
    {
        if ($order->status !== 'pending') {
            return;
        }

        $push->sendToUser(
            $order->seller_id,
            'Narudžba čeka tvoj odgovor ⏳',
            'Imaš neriješenu narudžbu. Prihvati ili odbij u najkraćem roku.',
            ['type' => 'order', 'orderId' => $order->id],
        );
    }

    private function orderAcceptedSeller(PushNotificationService $push, Order $order): void
    {
        if ($order->status !== 'accepted' || $order->delivery_method === 'pickup') {
            return;
        }

        $push->sendToUser(
            $order->seller_id,
            'Označi paket kao poslan 📦',
            'Kupac čeka informaciju o paketu. Označi narudžbu kao poslanu čim pošalješ.',
            ['type' => 'order', 'orderId' => $order->id],
        );
    }

    private function orderAcceptedBuyerPickup(PushNotificationService $push, Order $order): void
    {
        if ($order->status !== 'accepted' || $order->delivery_method !== 'pickup') {
            return;
        }

        $push->sendToUser(
            $order->buyer_id,
            'Označi narudžbu kao završenu ✅',
            'Jesi li preuzeo/la artikal? Označi narudžbu kao završenu.',
            ['type' => 'order', 'orderId' => $order->id],
        );
    }

    private function orderShippedBuyer(PushNotificationService $push, Order $order): void
    {
        if ($order->status !== 'shipped') {
            return;
        }

        $push->sendToUser(
            $order->buyer_id,
            'Jesi li primio/la paket? 📬',
            'Potvrdi dostavu ili označi narudžbu kao završenu kad primiš paket.',
            ['type' => 'order', 'orderId' => $order->id],
        );
    }

    private function handleOffer(PushNotificationService $push): void
    {
        $offer = Offer::find($this->modelId);
        if (! $offer) {
            return;
        }

        match ($this->reminderType) {
            'pending_seller'  => $this->offerPendingSeller($push, $offer),
            'countered_buyer' => $this->offerCounteredBuyer($push, $offer),
            default           => null,
        };
    }

    private function offerPendingSeller(PushNotificationService $push, Offer $offer): void
    {
        if ($offer->status !== 'pending') {
            return;
        }

        $push->sendToUser(
            $offer->seller_id,
            'Ponuda čeka tvoj odgovor 💰',
            'Imaš ponudu koja čeka. Prihvati, odbij ili kontriraraj.',
            ['type' => 'offer', 'offerId' => $offer->id],
        );
    }

    private function offerCounteredBuyer(PushNotificationService $push, Offer $offer): void
    {
        if ($offer->status !== 'countered') {
            return;
        }

        $push->sendToUser(
            $offer->buyer_id,
            'Kontra-ponuda čeka tvoj odgovor 💬',
            'Prodavač je predložio novu cijenu. Prihvati ili odbij ponudu.',
            ['type' => 'offer', 'offerId' => $offer->id],
        );
    }

    private function handleTrade(PushNotificationService $push): void
    {
        $trade = Trade::find($this->modelId);
        if (! $trade) {
            return;
        }

        match ($this->reminderType) {
            'active_seller'   => $this->tradeActiveSeller($push, $trade),
            'countered_buyer' => $this->tradeCounteredBuyer($push, $trade),
            default           => null,
        };
    }

    private function tradeActiveSeller(PushNotificationService $push, Trade $trade): void
    {
        if ($trade->status !== 'active') {
            return;
        }

        $push->sendToUser(
            $trade->seller_id,
            'Zamjena čeka tvoj odgovor 🔄',
            'Imaš prijedlog zamjene koji čeka. Prihvati, odbij ili predloži drugu zamjenu.',
            ['type' => 'trade', 'tradeId' => $trade->id],
        );
    }

    private function tradeCounteredBuyer(PushNotificationService $push, Trade $trade): void
    {
        if ($trade->status !== 'countered') {
            return;
        }

        $push->sendToUser(
            $trade->buyer_id,
            'Kontra-zamjena čeka tvoj odgovor 🔄',
            'Prodavač je predložio drugu zamjenu. Pogledaj i odgovori.',
            ['type' => 'trade', 'tradeId' => $trade->id],
        );
    }
}
