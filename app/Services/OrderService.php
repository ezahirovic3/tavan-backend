<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShippingOption;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Creates an order for one or more products from the same seller (bundle buy).
     * Products are locked and re-validated inside the transaction so two buyers
     * can't order the same item at the same time.
     *
     * @param  array<string>  $productIds
     *
     * @throws ValidationException
     */
    public function createDirect(User $buyer, array $productIds, array $data): Order
    {
        return DB::transaction(function () use ($buyer, $productIds, $data) {
            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get();

            $this->assertPurchasable($buyer, $products, $productIds);

            $deliveryMethod = $data['delivery_method'] ?? 'delivery';
            $shippingCost   = $this->resolveShippingCost($products, $deliveryMethod);

            $offer    = $this->resolveOffer($buyer, $products, $data['offer_id'] ?? null);
            $subtotal = (float) $products->sum('price');
            $discount = $offer ? max(0, (float) $products->first()->price - (float) $offer->offered_price) : 0;

            $order = Order::create([
                'order_number'    => $this->generateOrderNumber(),
                'buyer_id'        => $buyer->id,
                'seller_id'       => $products->first()->seller_id,
                'offer_id'        => $offer?->id,
                'subtotal'        => $subtotal,
                'discount'        => $discount,
                'shipping_cost'   => $shippingCost,
                'total'           => $subtotal - $discount + $shippingCost,
                'payment_method'  => $data['payment_method'],
                'delivery_method' => $data['delivery_method'],
                'status'          => 'pending',
                'shipping_name'   => $data['shipping_name'] ?? null,
                'shipping_street' => $data['shipping_street'] ?? null,
                'shipping_city'   => $data['shipping_city'] ?? null,
                'shipping_phone'  => $data['shipping_phone'] ?? null,
            ]);

            foreach ($products as $product) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'price'      => $product->price,
                ]);
            }

            if ($offer) {
                $offer->update(['status' => 'ordered']);
            }

            // Reserved = buyer committed, but seller hasn't confirmed yet
            Product::whereIn('id', $products->pluck('id'))->update(['status' => 'reserved']);

            return $order;
        });
    }

    /**
     * @param  Collection<int, Product>  $products
     *
     * @throws ValidationException
     */
    private function assertPurchasable(User $buyer, Collection $products, array $requestedIds): void
    {
        $unavailable = $products->reject(fn ($product) => $product->status === 'active');

        if (count($requestedIds) !== $products->count() || $unavailable->isNotEmpty()) {
            $titles = $unavailable->pluck('title')->implode(', ');

            throw ValidationException::withMessages([
                'product_ids' => $titles !== ''
                    ? "Sljedeći artikli više nisu dostupni: {$titles}."
                    : 'Proizvod nije dostupan.',
            ]);
        }

        if ($products->contains(fn ($product) => $product->seller_id === $buyer->id)) {
            throw ValidationException::withMessages([
                'product_ids' => 'Ne možete kupiti vlastiti proizvod.',
            ]);
        }

        if ($products->pluck('seller_id')->unique()->count() > 1) {
            throw ValidationException::withMessages([
                'product_ids' => 'Svi artikli u narudžbi moraju biti od istog prodavca.',
            ]);
        }
    }

    /**
     * Offers apply to single-item orders only (bundle offers are a future feature).
     * The offer must belong to this buyer, reference the ordered product,
     * and be in 'accepted' status.
     *
     * @param  Collection<int, Product>  $products
     *
     * @throws ValidationException
     */
    private function resolveOffer(User $buyer, Collection $products, ?string $offerId): ?Offer
    {
        if (! $offerId) {
            return null;
        }

        $offer = Offer::find($offerId);

        $valid = $offer
            && $products->count() === 1
            && $offer->buyer_id === $buyer->id
            && $offer->product_id === $products->first()->id
            && $offer->status === 'accepted';

        if (! $valid) {
            throw ValidationException::withMessages([
                'offer_id' => 'Ponuda se ne može primijeniti na ovu narudžbu.',
            ]);
        }

        return $offer;
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

    /**
     * Bundle rule: one parcel, the most expensive item to ship drives the price.
     * Free only when every item ships free.
     *
     * @param  Collection<int, Product>  $products
     */
    private function resolveShippingCost(Collection $products, string $deliveryMethod): float
    {
        if ($deliveryMethod === 'pickup') {
            return 0.0;
        }

        return (float) $products->map(fn ($product) => $this->resolveProductShippingCost($product))->max();
    }

    private function resolveProductShippingCost(Product $product): float
    {
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
