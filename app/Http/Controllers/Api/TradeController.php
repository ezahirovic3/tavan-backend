<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trade\StoreTradeRequest;
use App\Http\Resources\TradeResource;
use App\Models\Product;
use App\Models\Trade;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TradeController extends Controller
{
    public function __construct(private readonly ConversationService $conversations) {}

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

        return response()->json(['data' => new TradeResource($trade->load('product', 'offeredProduct', 'buyer', 'seller'))], 201);
    }

    public function show(Request $request, Trade $trade): JsonResponse
    {
        $this->authorize('view', $trade);

        return response()->json(['data' => new TradeResource($trade->load('product', 'offeredProduct', 'buyer', 'seller'))]);
    }

    public function accept(Request $request, Trade $trade): JsonResponse
    {
        $this->authorize('respond', $trade);

        $trade->update(['status' => 'accepted']);

        // Mark both products as sold
        Product::whereIn('id', [$trade->product_id, $trade->offered_product_id])
            ->update(['status' => 'sold']);

        return response()->json(['data' => new TradeResource($trade->fresh()->load('product', 'offeredProduct', 'buyer', 'seller'))]);
    }

    public function decline(Request $request, Trade $trade): JsonResponse
    {
        $this->authorize('respond', $trade);

        $trade->update(['status' => 'declined']);

        $conversation = $this->conversations->findOrCreate($trade->buyer_id, $trade->seller_id);
        $this->conversations->sendSystemMessage($conversation, $request->user()->id, 'system_status', [
            'tradeId' => $trade->id,
            'status'  => 'declined',
        ]);

        return response()->json(['data' => new TradeResource($trade->fresh()->load('product', 'offeredProduct', 'buyer', 'seller'))]);
    }

    public function counter(Request $request, Trade $trade): JsonResponse
    {
        $this->authorize('respond', $trade);

        $trade->update(['status' => 'countered']);

        $conversation = $this->conversations->findOrCreate($trade->buyer_id, $trade->seller_id);
        $this->conversations->sendSystemMessage($conversation, $request->user()->id, 'system_status', [
            'tradeId' => $trade->id,
            'status'  => 'countered',
        ]);

        return response()->json(['data' => new TradeResource($trade->fresh()->load('product', 'offeredProduct', 'buyer', 'seller'))]);
    }
}
