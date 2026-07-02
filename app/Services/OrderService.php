<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShippingOption;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createDirect(User $buyer, Product $product, array $data): Order
    {
        return DB::transaction(function () use ($buyer, $product, $data) {
            $shippingCost = $this->resolveShippingCost($product, $data['delivery_method'] ?? 'delivery');

            // Apply offer discount if an accepted offer is referenced
            $offerId  = $data['offer_id'] ?? null;
            $offer    = $offerId ? \App\Models\Offer::find($offerId) : null;
            $subtotal = $product->price;
            $discount = $offer ? max(0, $subtotal - $offer->offered_price) : 0;
            $effectivePrice = $offer ? $offer->offered_price : $subtotal;

            $order = Order::create([
                'order_number'    => $this->generateOrderNumber(),
                'buyer_id'        => $buyer->id,
                'seller_id'       => $product->seller_id,
                'offer_id'        => $offerId,
                'subtotal'        => $subtotal,
                'discount'        => $discount,
                'shipping_cost'   => $shippingCost,
                'total'           => $effectivePrice + $shippingCost,
                'payment_method'  => $data['payment_method'],
                'delivery_method' => $data['delivery_method'],
                'status'          => 'pending',
                'shipping_name'   => $data['shipping_name'] ?? null,
                'shipping_street' => $data['shipping_street'] ?? null,
                'shipping_city'   => $data['shipping_city'] ?? null,
                'shipping_phone'  => $data['shipping_phone'] ?? null,
            ]);

            OrderItem::create([
                'order_id'   => $order->id,
                'product_id' => $product->id,
                'price'      => $subtotal,
            ]);

            if ($offer) {
                $offer->update(['status' => 'ordered']);
            }

            // Reserved = buyer committed, but seller hasn't confirmed yet
            $product->update(['status' => 'reserved']);

            return $order;
        });
    }

    public function createFromTrade(Trade $trade): Order
    {
        return DB::transaction(function () use ($trade) {
            $product = $trade->product;

            $order = Order::create([
                'order_number'    => $this->generateOrderNumber(),
                'buyer_id'        => $trade->buyer_id,
                'seller_id'       => $trade->seller_id,
                'trade_id'        => $trade->id,
                'subtotal'        => 0,
                'discount'        => 0,
                'shipping_cost'   => 0,
                'total'           => 0,
                'payment_method'  => 'trade',
                'delivery_method' => 'pickup',
                'status'          => 'accepted',
            ]);

            OrderItem::create([
                'order_id'   => $order->id,
                'product_id' => $product->id,
                'price'      => 0,
            ]);

            // Reserve both products until the trade is completed
            Product::whereIn('id', [$trade->product_id, $trade->offered_product_id])
                ->update(['status' => 'reserved']);

            return $order;
        });
    }

    private function resolveShippingCost(Product $product, string $deliveryMethod): float
    {
        if ($deliveryMethod === 'pickup') {
            return 0.0;
        }

        if ($product->free_shipping) {
            return 0.0;
        }

        if ($product->exact_shipping_price !== null) {
            return (float) $product->exact_shipping_price;
        }

        $option = ShippingOption::where('size', $product->shipping_size)->where('is_active', true)->first();

        return $option ? (float) $option->price : 0.0;
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = '#'.str_pad((string) random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
