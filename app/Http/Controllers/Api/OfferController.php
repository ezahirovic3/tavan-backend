<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Offer\AcceptOfferRequest;
use App\Http\Requests\Offer\CounterOfferRequest;
use App\Http\Requests\Offer\StoreOfferRequest;
use App\Http\Resources\OfferResource;
use App\Models\Offer;
use App\Models\Product;
use App\Services\ConversationService;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversations,
        private readonly PushNotificationService $push,
    ) {}

    public function store(StoreOfferRequest $request): JsonResponse
    {
        $product = Product::findOrFail($request->product_id);

        abort_if(! $product->allows_offers, 422, 'Ovaj proizvod ne prihvata ponude.');
        abort_if($product->seller_id === $request->user()->id, 422, 'Ne možete napraviti ponudu na vlastiti proizvod.');
        abort_if($product->status !== 'active', 422, 'Proizvod nije dostupan.');

        $offer = Offer::create([
            'product_id'    => $product->id,
            'buyer_id'      => $request->user()->id,
            'seller_id'     => $product->seller_id,
            'offered_price' => $request->offered_price,
            'message'       => $request->message,
            'status'        => 'pending',
            'expires_at'    => now()->addDays(3),
        ]);

        $conversation = $this->conversations->findOrCreate(
            $request->user()->id,
            $product->seller_id,
            $product->id
        );
        $this->conversations->sendSystemMessage($conversation, $request->user()->id, 'system_offer', ['offerId' => $offer->id]);

        $this->push->sendToUser(
            $product->seller_id,
            'Nova ponuda — ' . $product->title,
            $request->user()->name . ' nudi ' . number_format($request->offered_price, 2) . ' KM',
            ['type' => 'offer', 'offerId' => $offer->id],
        );

        return response()->json(['data' => new OfferResource($offer->load('product', 'buyer', 'seller'))], 201);
    }

    public function show(Request $request, Offer $offer): JsonResponse
    {
        $this->authorize('view', $offer);

        return response()->json(['data' => new OfferResource($offer->load('product', 'buyer', 'seller'))]);
    }

    public function accept(AcceptOfferRequest $request, Offer $offer): JsonResponse
    {
        $this->authorize('respond', $offer);

        $offer->update(['status' => 'accepted']);

        $conversation = $this->conversations->findOrCreate($offer->buyer_id, $offer->seller_id);
        $this->conversations->sendSystemMessage($conversation, $request->user()->id, 'system_status', [
            'offerId' => $offer->id,
            'status'  => 'accepted',
        ]);

        $this->push->sendToUser(
            $offer->buyer_id,
            'Ponuda prihvaćena! 🎉',
            'Prodavač je prihvatio vašu ponudu. Nastavi na plaćanje.',
            ['type' => 'offer', 'offerId' => $offer->id, 'status' => 'accepted'],
        );

        return response()->json(['data' => new OfferResource($offer->fresh()->load('product', 'buyer', 'seller'))]);
    }

    public function decline(Request $request, Offer $offer): JsonResponse
    {
        $this->authorize('respond', $offer);

        $offer->update(['status' => 'declined']);

        $conversation = $this->conversations->findOrCreate($offer->buyer_id, $offer->seller_id);
        $this->conversations->sendSystemMessage($conversation, $request->user()->id, 'system_status', ['offerId' => $offer->id, 'status' => 'declined']);

        $this->push->sendToUser(
            $offer->buyer_id,
            'Ponuda odbijena',
            'Prodavač je odbio vašu ponudu.',
            ['type' => 'offer', 'offerId' => $offer->id, 'status' => 'declined'],
        );

        return response()->json(['data' => new OfferResource($offer->fresh()->load('product', 'buyer', 'seller'))]);
    }

    public function counter(CounterOfferRequest $request, Offer $offer): JsonResponse
    {
        $this->authorize('respond', $offer);

        $offer->update([
            'status'        => 'countered',
            'counter_price' => $request->counter_price,
        ]);

        $conversation = $this->conversations->findOrCreate($offer->buyer_id, $offer->seller_id);
        $this->conversations->sendSystemMessage($conversation, $request->user()->id, 'system_offer', ['offerId' => $offer->id, 'status' => 'countered']);

        $this->push->sendToUser(
            $offer->buyer_id,
            'Kontra-ponuda',
            'Prodavač je ponudio ' . number_format($request->counter_price, 2) . ' KM.',
            ['type' => 'offer', 'offerId' => $offer->id, 'status' => 'countered'],
        );

        return response()->json(['data' => new OfferResource($offer->fresh()->load('product', 'buyer', 'seller'))]);
    }
}
