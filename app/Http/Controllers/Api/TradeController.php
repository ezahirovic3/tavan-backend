<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trade\StoreTradeRequest;
use App\Http\Resources\TradeResource;
use App\Models\Product;
use App\Models\Trade;
use App\Services\ConversationService;
use App\Services\OrderService;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TradeController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversations,
        private readonly PushNotificationService $push,
        private readonly OrderService $orders,
    ) {}

    public function store(StoreTradeRequest $request): JsonResponse
    {
        $product        = Product::findOrFail($request->product_id);
        $offeredProduct = Product::findOrFail($request->offered_product_id);

        abort_if(! $product->allows_trades, 422, 'Ovaj proizvod ne prihvata zamjene.');
        abort_if($product->seller_id === $request->user()->id, 422, 'Ne možete predložiti zamjenu za vlastiti proizvod.');
        abort_if($offeredProduct->seller_id !== $request->user()->id, 422, 'Možete ponuditi samo vlastite proizvode.');
        abort_if($product->status !== 'active', 422, 'Proizvod nije dostupan.');
        abort_if($offeredProduct->status !== 'active', 422, 'Ponuđeni proizvod nije dostupan.');

        $trade = Trade::create([
            'product_id'         => $product->id,
            'offered_product_id' => $offeredProduct->id,
            'buyer_id'           => $request->user()->id,
            'seller_id'          => $product->seller_id,
            'message'            => $request->message,
            'status'             => 'active',
        ]);

        $conversation = $this->conversations->findOrCreate($request->user()->id, $product->seller_id, $product->id);
        $this->conversations->sendSystemMessage($conversation, $request->user()->id, 'system_trade', ['tradeId' => $trade->id]);

        $this->push->sendToUser(
            $product->seller_id,
            'Nova zamjena — ' . $product->title,
            $request->user()->name . ' želi zamijeniti za "' . $offeredProduct->title . '".',
            ['type' => 'trade', 'tradeId' => $trade->id, 'conversationId' => $conversation->id],
        );

        return response()->json(['data' => new TradeResource($trade->load('product.images', 'offeredProduct.images', 'buyer', 'seller'))], 201);
    }

    public function show(Request $request, Trade $trade): JsonResponse
    {
        $this->authorize('view', $trade);

        return response()->json(['data' => new TradeResource($trade->load('product.images', 'offeredProduct.images', 'buyer', 'seller', 'order'))]);
    }

    public function accept(Request $request, Trade $trade): JsonResponse
    {
        $this->authorize('respond', $trade);

        $trade->update(['status' => 'accepted']);

        $order = $this->orders->createFromTrade($trade);

        $conversation = $this->conversations->findOrCreate($trade->buyer_id, $trade->seller_id);
        $this->conversations->sendSystemMessage($conversation, $request->user()->id, 'system_status', [
            'tradeId' => $trade->id,
            'orderId' => $order->id,
            'status'  => 'accepted',
        ], 'Prodavač je prihvatio zamjenu.');

        $this->push->sendToUser(
            $trade->buyer_id,
            'Zamjena prihvaćena! 🎉',
            'Prodavač je prihvatio vašu zamjenu.',
            ['type' => 'trade', 'tradeId' => $trade->id, 'orderId' => $order->id, 'conversationId' => $conversation->id, 'status' => 'accepted'],
        );

        return response()->json(['data' => new TradeResource($trade->fresh()->load('product.images', 'offeredProduct.images', 'buyer', 'seller', 'order'))]);
    }

    public function decline(Request $request, Trade $trade): JsonResponse
    {
        $this->authorize('respond', $trade);

        $trade->update(['status' => 'declined']);

        $conversation = $this->conversations->findOrCreate($trade->buyer_id, $trade->seller_id);
        $this->conversations->sendSystemMessage($conversation, $request->user()->id, 'system_status', [
            'tradeId' => $trade->id,
            'status'  => 'declined',
        ], 'Prodavač je odbio zamjenu.');

        $this->push->sendToUser(
            $trade->buyer_id,
            'Zamjena odbijena',
            'Prodavač je odbio vašu zamjenu.',
            ['type' => 'trade', 'tradeId' => $trade->id, 'conversationId' => $conversation->id, 'status' => 'declined'],
        );

        return response()->json(['data' => new TradeResource($trade->fresh()->load('product.images', 'offeredProduct.images', 'buyer', 'seller'))]);
    }

    public function counter(Request $request, Trade $trade): JsonResponse
    {
        $this->authorize('respond', $trade);

        $trade->update(['status' => 'countered']);

        $conversation = $this->conversations->findOrCreate($trade->buyer_id, $trade->seller_id);
        $this->conversations->sendSystemMessage($conversation, $request->user()->id, 'system_status', [
            'tradeId' => $trade->id,
            'status'  => 'countered',
        ], 'Prodavač je predložio kontra-zamjenu.');

        $this->push->sendToUser(
            $trade->buyer_id,
            'Kontra-zamjena',
            'Prodavač je predložio kontra-zamjenu.',
            ['type' => 'trade', 'tradeId' => $trade->id, 'conversationId' => $conversation->id, 'status' => 'countered'],
        );

        return response()->json(['data' => new TradeResource($trade->fresh()->load('product.images', 'offeredProduct.images', 'buyer', 'seller'))]);
    }
}
