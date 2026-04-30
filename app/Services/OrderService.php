<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingOption;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createDirect(User $buyer, Product $product, array $data): Order
    {
        return DB::transaction(function () use ($buyer, $product, $data) {
            $shippingCost = $this->resolveShippingCost($product->shipping_size);

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
                'product_id'      => $product->id,
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

            // Reserved = buyer committed, but seller hasn't confirmed yet
            $product->update(['status' => 'reserved']);

            return $order;
        });
    }

    public function createFromOffer(Offer $offer, array $shippingData): Order
    {
        return DB::transaction(function () use ($offer, $shippingData) {
            $product      = $offer->product;
            $shippingCost = $this->resolveShippingCost($product->shipping_size);
            $subtotal     = $product->price;
            $discount     = max(0, $subtotal - $offer->offered_price);

            $order = Order::create([
                'order_number'    => $this->generateOrderNumber(),
                'buyer_id'        => $offer->buyer_id,
                'seller_id'       => $offer->seller_id,
                'product_id'      => $product->id,
                'offer_id'        => $offer->id,
                'subtotal'        => $subtotal,
                'discount'        => $discount,
                'shipping_cost'   => $shippingCost,
                'total'           => $offer->offered_price + $shippingCost,
                'payment_method'  => $shippingData['payment_method'],
                'delivery_method' => $shippingData['delivery_method'],
                'status'          => 'pending',
                'shipping_name'   => $shippingData['shipping_name'],
                'shipping_street' => $shippingData['shipping_street'],
                'shipping_city'   => $shippingData['shipping_city'],
                'shipping_phone'  => $shippingData['shipping_phone'],
            ]);

            $offer->update(['status' => 'accepted']);
            $product->update(['status' => 'reserved']);

            return $order;
        });
    }

    private function resolveShippingCost(string $size): float
    {
        $option = ShippingOption::where('size', $size)->where('is_active', true)->first();

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
