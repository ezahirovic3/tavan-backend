<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Jobs\SendReminderNotificationJob;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\ConversationService;
use App\Services\OrderService;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly ConversationService $conversations,
        private readonly PushNotificationService $push,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $role   = $request->query('role'); // 'buyer' | 'seller' | null (both)

        $orders = Order::when($role === 'buyer',  fn ($q) => $q->where('buyer_id',  $userId))
            ->when($role === 'seller', fn ($q) => $q->where('seller_id', $userId))
            ->when(!$role,             fn ($q) => $q->where(fn ($q) => $q
                ->where('buyer_id',  $userId)
                ->orWhere('seller_id', $userId)
            ))
            ->with(['product.images', 'items.product.images', 'buyer', 'seller'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $product = Product::findOrFail($request->product_id);

        abort_if($product->seller_id === $request->user()->id, 422, 'Ne možete kupiti vlastiti proizvod.');
        abort_if($product->status !== 'active', 422, 'Proizvod nije dostupan.');

        $order = $this->orderService->createDirect($request->user(), $product, $request->validated());

        $conversation = $this->conversations->findOrCreate($request->user()->id, $product->seller_id, $product->id);
        $this->conversations->sendSystemMessage($conversation, $request->user()->id, 'system_order', ['orderId' => $order->id]);

        $this->push->sendToUser(
            $product->seller_id,
            'Nova narudžba! 🛍️',
            $request->user()->name . ' je naručio/la "' . $product->title . '".',
            ['type' => 'order', 'orderId' => $order->id],
        );

        SendReminderNotificationJob::dispatch('order', $order->id, 'pending_seller')
            ->delay(now()->addHours(24));

        return response()->json(['data' => new OrderResource($order->load('product', 'items.product.images', 'buyer', 'seller'))], 201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json(['data' => new OrderResource($order->load('product.images', 'items.product.images', 'buyer', 'seller', 'offer', 'trade.offeredProduct.images', 'reviews.reviewer'))]);
    }

    public function accept(Request $request, Order $order): JsonResponse
    {
        $this->authorize('sellerAction', $order);
        abort_if($order->status !== 'pending', 422, 'Narudžba nije u statusu čekanja.');

        $order->update(['status' => 'accepted']);
        $this->systemStatus($order, $request->user(), 'accepted');

        // Remind seller to ship (delivery), or buyer to complete (pickup)
        SendReminderNotificationJob::dispatch('order', $order->id, 'accepted_seller')
            ->delay(now()->addHours(24));
        SendReminderNotificationJob::dispatch('order', $order->id, 'accepted_buyer_pickup')
            ->delay(now()->addHours(24));

        return response()->json(['data' => new OrderResource($order->fresh()->load('product', 'items.product.images', 'buyer', 'seller'))]);
    }

    public function ship(Request $request, Order $order): JsonResponse
    {
        $this->authorize('sellerAction', $order);
        abort_if($order->status !== 'accepted', 422, 'Narudžba mora biti prihvaćena prije slanja.');

        $order->update(['status' => 'shipped']);
        $this->systemStatus($order, $request->user(), 'shipped');

        SendReminderNotificationJob::dispatch('order', $order->id, 'shipped_buyer')
            ->delay(now()->addHours(48));

        return response()->json(['data' => new OrderResource($order->fresh()->load('product', 'items.product.images', 'buyer', 'seller'))]);
    }

    public function deliver(Request $request, Order $order): JsonResponse
    {
        $this->authorize('buyerAction', $order);
        abort_if($order->status !== 'shipped', 422, 'Narudžba još nije poslana.');

        $order->update(['status' => 'delivered']);
        $this->systemStatus($order, $request->user(), 'delivered');

        return response()->json(['data' => new OrderResource($order->fresh()->load('product', 'items.product.images', 'buyer', 'seller'))]);
    }

    public function complete(Request $request, Order $order): JsonResponse
    {
        $this->authorize('complete', $order);

        $isPickup = $order->delivery_method === 'pickup';
        $validStatuses = $isPickup ? ['accepted'] : ['shipped', 'delivered'];
        abort_if(! in_array($order->status, $validStatuses), 422, $isPickup ? 'Narudžba nije prihvaćena.' : 'Narudžba još nije poslana.');

        $order->update(['status' => 'completed']);
        $order->items->each(fn ($item) => $item->product?->update(['status' => 'sold']));

        if ($order->trade_id) {
            $order->trade->offeredProduct()->update(['status' => 'sold']);
            $order->trade->update(['status' => 'completed']);
        }

        $this->systemStatus($order, $request->user(), 'completed');

        return response()->json(['data' => new OrderResource($order->fresh()->load('product', 'items.product.images', 'buyer', 'seller', 'trade.offeredProduct.images'))]);
    }

    public function decline(Request $request, Order $order): JsonResponse
    {
        $this->authorize('decline', $order);

        $isBuyer = $request->user()->id === $order->buyer_id;

        if ($isBuyer) {
            // Buyer can only cancel while the order is still pending
            abort_if($order->status !== 'pending', 422, 'Možeš otkazati samo narudžbu u čekanju.');
        } else {
            // Seller can decline pending orders or accepted ones (e.g. buyer never showed up)
            abort_if(! in_array($order->status, ['pending', 'accepted']), 422, 'Ova narudžba ne može biti odbijena.');
        }

        $order->update(['status' => 'declined']);
        $order->items->each(fn ($item) => $item->product?->update(['status' => 'active']));

        if ($order->trade_id) {
            $order->trade->offeredProduct()->update(['status' => 'active']);
            $order->trade->update(['status' => 'declined']);
        }

        $this->systemStatus($order, $request->user(), 'declined');

        return response()->json(['data' => new OrderResource($order->fresh()->load('product', 'items.product.images', 'buyer', 'seller', 'trade.offeredProduct.images'))]);
    }

    private function systemStatus(Order $order, User $actor, string $status): void
    {
        $isBuyerActor = $actor->id === $order->buyer_id;
        $handle       = '@' . $actor->username;

        $statusText = match ($status) {
            'accepted'  => $handle . ' je prihvatio/la narudžbu.',
            'shipped'   => $handle . ' je poslao/la paket.',
            'delivered' => $handle . ' je potvrdio/la dostavu.',
            'completed' => $handle . ' je potvrdio/la završetak narudžbe.',
            'declined'  => $isBuyerActor
                            ? $handle . ' je otkazao/la narudžbu.'
                            : $handle . ' je odbio/la narudžbu.',
            default     => 'Status narudžbe je promijenjen.',
        };

        $conversation = $this->conversations->findOrCreate($order->buyer_id, $order->seller_id);
        $this->conversations->sendSystemMessage(
            $conversation,
            $actor->id,
            'system_status',
            ['orderId' => $order->id, 'status' => $status],
            $statusText,
        );

        // Push notification to the OTHER party
        $recipientId = $isBuyerActor ? $order->seller_id : $order->buyer_id;

        [$title, $pushBody] = match ($status) {
            'accepted'  => ['Narudžba prihvaćena ✅', $handle . ' je prihvatio/la vašu narudžbu.'],
            'shipped'   => ['Paket poslan 📦', 'Vaša narudžba je na putu!'],
            'delivered' => ['Potvrda dostave', $handle . ' je potvrdio/la dostavu.'],
            'completed' => ['Narudžba završena 🎉', 'Narudžba je uspješno završena.'],
            'declined'  => $isBuyerActor
                            ? ['Narudžba otkazana', $handle . ' je otkazao/la narudžbu.']
                            : ['Narudžba odbijena', $handle . ' je odbio/la vašu narudžbu.'],
            default     => ['Ažuriranje narudžbe', 'Status vaše narudžbe je promijenjen.'],
        };

        $this->push->sendToUser(
            $recipientId,
            $title,
            $pushBody,
            ['type' => 'order', 'orderId' => $order->id, 'status' => $status],
        );
    }
}
