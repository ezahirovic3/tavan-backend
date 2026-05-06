<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
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
            ->with(['product.images', 'buyer', 'seller'])
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
            $request->user()->name . ' je kupio/la "' . $product->title . '".',
            ['type' => 'order', 'orderId' => $order->id],
        );

        return response()->json(['data' => new OrderResource($order->load('product', 'buyer', 'seller'))], 201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json(['data' => new OrderResource($order->load('product.images', 'buyer', 'seller', 'offer', 'reviews.reviewer'))]);
    }

    public function accept(Request $request, Order $order): JsonResponse
    {
        $this->authorize('sellerAction', $order);
        abort_if($order->status !== 'pending', 422, 'Narudžba nije u statusu čekanja.');

        $order->update(['status' => 'accepted']);
        $this->systemStatus($order, $request->user()->id, 'accepted');

        return response()->json(['data' => new OrderResource($order->fresh()->load('product', 'buyer', 'seller'))]);
    }

    public function ship(Request $request, Order $order): JsonResponse
    {
        $this->authorize('sellerAction', $order);
        abort_if($order->status !== 'accepted', 422, 'Narudžba mora biti prihvaćena prije slanja.');

        $order->update(['status' => 'shipped']);
        $this->systemStatus($order, $request->user()->id, 'shipped');

        return response()->json(['data' => new OrderResource($order->fresh()->load('product', 'buyer', 'seller'))]);
    }

    public function deliver(Request $request, Order $order): JsonResponse
    {
        $this->authorize('buyerAction', $order);
        abort_if($order->status !== 'shipped', 422, 'Narudžba još nije poslana.');

        $order->update(['status' => 'delivered']);
        $this->systemStatus($order, $request->user()->id, 'delivered');

        return response()->json(['data' => new OrderResource($order->fresh()->load('product', 'buyer', 'seller'))]);
    }

    public function complete(Request $request, Order $order): JsonResponse
    {
        $this->authorize('buyerAction', $order);
        abort_if($order->status !== 'delivered', 422, 'Narudžba još nije isporučena.');

        $order->update(['status' => 'completed']);
        $order->product->update(['status' => 'sold']);
        $this->systemStatus($order, $request->user()->id, 'completed');

        return response()->json(['data' => new OrderResource($order->fresh()->load('product', 'buyer', 'seller'))]);
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
        $order->product->update(['status' => 'active']);
        $this->systemStatus($order, $request->user()->id, 'declined');

        return response()->json(['data' => new OrderResource($order->fresh()->load('product', 'buyer', 'seller'))]);
    }

    private function systemStatus(Order $order, string $actorId, string $status): void
    {
        $isBuyerActor = $actorId === $order->buyer_id;

        // Human-readable text stored in message body — shown in chat as a status line
        $statusText = match ($status) {
            'accepted'  => 'Narudžba je prihvaćena.',
            'shipped'   => 'Prodavač je poslao paket.',
            'delivered' => 'Kupac je potvrdio dostavu.',
            'completed' => 'Narudžba je uspješno završena.',
            'declined'  => $isBuyerActor
                            ? 'Kupac je otkazao narudžbu.'
                            : 'Prodavač je odbio narudžbu.',
            default     => 'Status narudžbe je promijenjen.',
        };

        $conversation = $this->conversations->findOrCreate($order->buyer_id, $order->seller_id);
        $this->conversations->sendSystemMessage(
            $conversation,
            $actorId,
            'system_status',
            ['orderId' => $order->id, 'status' => $status],
            $statusText,
        );

        // Push notification to the OTHER party
        $recipientId = $isBuyerActor ? $order->seller_id : $order->buyer_id;

        [$title, $pushBody] = match ($status) {
            'accepted'  => ['Narudžba prihvaćena ✅', 'Prodavač je prihvatio vašu narudžbu.'],
            'shipped'   => ['Paket poslan 📦', 'Vaša narudžba je na putu!'],
            'delivered' => ['Potvrda dostave', 'Kupac je potvrdio dostavu.'],
            'completed' => ['Narudžba završena 🎉', 'Narudžba je uspješno završena.'],
            'declined'  => $isBuyerActor
                            ? ['Narudžba otkazana', 'Kupac je otkazao narudžbu.']
                            : ['Narudžba odbijena', 'Prodavač je odbio vašu narudžbu.'],
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
