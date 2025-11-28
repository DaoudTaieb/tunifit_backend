<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Notification;
use App\Models\Product;
use App\Events\OrderPlacedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Stripe\StripeClient;
use Illuminate\Validation\ValidationException;
use Stripe\Webhook;
use App\Models\User;
use App\Models\Cart;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['items.product'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'orderNumber' => $order->order_number,
                    'totalAmount' => $order->total_amount,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'payment_method' => $order->payment_method,
                    'createdAt' => $order->created_at,
                    'items' => $order->items->map(function ($item) {
                        return [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'price' => $item->price,
                            'quantity' => $item->quantity,
                            'image' => $item->product->images[0] ?? null,
                        ];
                    }),
                ];
            });
            
        return response()->json($orders);
    }

    public function show($id, Request $request)
    {
        $order = Order::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with(['items.product', 'user'])
            ->firstOrFail();
            
        return response()->json([
            'id' => $order->id,
            'orderNumber' => $order->order_number,
            'totalAmount' => $order->total_amount,
            'status' => $order->status,
            'createdAt' => $order->created_at,
            'shippingAddress' => $order->shipping_address,
            'payment_status' => $order->payment_status,
            'paymentMethod' => $order->payment_method,
            'user' => [
                'firstName' => $order->user->first_name,
                'lastName' => $order->user->last_name,
                'email' => $order->user->email,
            ],
            'items' => $order->items->map(function ($item) {
                return [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'image' => $item->product->images[0] ?? null,
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.size' => 'nullable|string',
            'shippingAddress' => 'required|array',
            'shippingAddress.firstName' => 'required|string',
            'shippingAddress.lastName' => 'required|string',
            'shippingAddress.address' => 'required|string',
            'shippingAddress.city' => 'required|string',
            'shippingAddress.postalCode' => 'required|string',
            'shippingAddress.country' => 'required|string',
            'shippingAddress.email' => 'required|email',
            'shippingAddress.phone' => 'nullable|string',
            'subtotal' => 'nullable|numeric',
            'tax' => 'nullable|numeric',
            'shipping' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'totalAmount' => 'required|numeric',
        ]);

        $user = $request->user();
        $subtotal = $request->input('subtotal', 0);
        $taxAmount = 0; // No tax
        $shippingCost = $request->input('shipping', 7.5); // Fixed shipping cost: 7.5 DT
        $discountAmount = $request->input('discount', 0);
        $finalTotal = $request->input('totalAmount', 0);

        // Recalculate if not provided
        if ($subtotal == 0) {
            foreach ($request->items as $it) {
                $product = Product::lockForUpdate()->findOrFail($it['id']);
                $qty = (int)$it['quantity'];

                if ($qty > $product->stock) {
                    throw ValidationException::withMessages([
                        'items' => ["Stock insuffisant pour le produit ID {$product->id}."]
                    ]);
                }

                $subtotal += ($product->promo_price ?? $product->price) * $qty;
            }
            
            // Fixed shipping cost: 7.5 DT
            if ($shippingCost == 0) {
                $shippingCost = 7.5;
            }
            
            // Total = montant + livraison (no tax)
            $finalTotal = $subtotal + $shippingCost - $discountAmount;
        }

        // Create the order
        $order = Order::create([
            'user_id' => $user->id,
            'total_amount' => $finalTotal,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_amount' => $shippingCost,
            'discount_amount' => $discountAmount,
            'payment_status' => 'pending',
            'status' => 'pending',
            'shipping_address' => $request->shippingAddress,
            'payment_method' => 'cash_on_delivery', // or 'bank_transfer' based on selection
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
        ]);

        // Create order items and reduce stock
        foreach ($request->items as $item) {
            $product = Product::lockForUpdate()->findOrFail($item['id']);
            $qty = (int)$item['quantity'];
            $selectedSize = $item['size'] ?? null;

            // Check stock by size if size is specified
            if ($selectedSize && $product->size_stock) {
                $sizeStock = $product->size_stock;
                $stockForSize = (int)($sizeStock[$selectedSize] ?? 0);
                
                if ($qty > $stockForSize) {
                    throw ValidationException::withMessages([
                        'items' => ["Stock insuffisant pour la taille {$selectedSize} du produit ID {$product->id}."]
                    ]);
                }
                
                // Reduce stock for specific size
                $sizeStock[$selectedSize] = max(0, $stockForSize - $qty);
                $product->size_stock = $sizeStock;
            }

            // Check total stock
            if ($qty > $product->stock) {
                throw ValidationException::withMessages([
                    'items' => ["Stock insuffisant pour le produit ID {$product->id}."]
                ]);
            }

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $qty,
                'price' => $product->promo_price ?? $product->price,
                'size' => $selectedSize,
            ]);

            // Reduce total stock
            $product->stock -= $qty;
            $product->save();
        }

        // Clear cart
        Cart::where('user_id', $user->id)->delete();

        // Create admin notification with product images
        $itemsPayload = collect($request->items)->map(function($i) {
            $product = Product::find($i['id']);
            return [
                'product_id' => $i['id'],
                'product_name' => $product->name ?? null,
                'quantity' => $i['quantity'],
                'image' => $product->images[0] ?? $i['image'] ?? null, // Include product image
                'price' => $product->promo_price ?? $product->price ?? null,
            ];
        })->values();

        try {
            $notification = Notification::create([
                'created_by' => $user->id,
                'type' => 'order.placed',
                'title' => 'Nouvelle commande',
                'message' => "Commande {$order->order_number} créée pour un montant de {$finalTotal} DT.",
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_amount' => $order->total_amount,
                    'items' => $itemsPayload,
                ],
            ]);

            // Fire event (but don't fail if Pusher is not available)
            try {
                event(new OrderPlacedNotification($notification, $order));
            } catch (\Exception $e) {
                \Log::warning('Failed to broadcast order notification: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to create order notification: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Commande créée avec succès',
            'order' => [
                'id' => $order->id,
                'orderNumber' => $order->order_number,
                'totalAmount' => $order->total_amount,
                'status' => $order->status,
            ],
        ], 201);
    }

    public function initiatePayment(Request $request)
{
    $request->validate([
        'items' => 'required|array',
        'items.*.id' => 'required|exists:products,id',
        'items.*.quantity' => 'required|integer|min:1',
        'shippingAddress' => 'required|array',
        'shippingAddress.firstName' => 'required|string',
        'shippingAddress.lastName' => 'required|string',
        'shippingAddress.address' => 'required|string',
        'shippingAddress.city' => 'required|string',
        'shippingAddress.postalCode' => 'required|string',
        'shippingAddress.country' => 'required|string',
    ]);

    $user = $request->user();
    $currency = 'tnd';
    $subtotal = 0; // Fixed shipping fee

    // 1️⃣ Check stock and calculate subtotal
    foreach ($request->items as $it) {
        $product = Product::lockForUpdate()->findOrFail($it['id']);
        $qty = (int)$it['quantity'];

        if ($qty > $product->stock) {
            throw ValidationException::withMessages([
                'items' => ["Insufficient stock for product ID {$product->id}."]
            ]);
        }

        $subtotal += $product->promo_price * $qty;
    }

    // 2️⃣ Apply 19% tax
    $taxRate = 0.19;
    $taxAmount = $subtotal * $taxRate;
    $shippingCost = ($subtotal < 50) ? 5.99 : 0;
    $finalTotal = $subtotal + $taxAmount + $shippingCost;


    // 3️⃣ Create Stripe line item (single item for total order including tax)
    $lineItems = [[
        'price_data' => [
            'currency' => $currency,
            'product_data' => ['name' => 'Order total (including 19% tax)'],
            'unit_amount' => (int) round($finalTotal * 1000 / 10) * 10, // Stripe TND minor units fix
        ],
        'quantity' => 1,
    ]];

    // 4️⃣ Create Stripe Checkout session
    $stripe = new StripeClient(config('services.stripe.secret'));
    $session = $stripe->checkout->sessions->create([
        'mode' => 'payment',
        'line_items' => $lineItems,
        'success_url' => env('FRONTEND_URL') . '/checkout/success?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => env('FRONTEND_URL') . '/checkout/cancelled',
        'client_reference_id' => (string)($user?->id ?? 'guest'),
        'payment_method_types' => ['card'],
        'metadata' => [
            'items' => json_encode($request->items),
            'shippingAddress' => json_encode($request->shippingAddress),
            'user_id' => (string)($user?->id ?? ''),
            'final_total' => $finalTotal,
        ],
    ]);

    // 5️⃣ Return session info to frontend
    return response()->json([
        'sessionId' => $session->id,
        'sessionUrl' => $session->url,
        'message' => 'Stripe checkout session created. Proceed to payment.',
    ], 201);
}


public function handleStripeWebhook(Request $request)
{
    $payload = $request->getContent();
    $sigHeader = $request->header('Stripe-Signature');
    $secret = config('services.stripe.webhook_secret');

    // 1️⃣ Verify the webhook signature
    try {
        $event = Webhook::constructEvent($payload, $sigHeader, $secret);
    } catch (\UnexpectedValueException $e) {
        return response('Invalid payload', 400);
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        return response('Invalid signature', 400);
    }

    // 2️⃣ Handle the checkout session completed event
    if ($event->type === 'checkout.session.completed') {
        $session = $event->data->object;

        // Check if order already exists (using Stripe session ID)
        $existingOrder = Order::where('stripe_session_id', $session->id)->first();
        if ($existingOrder) {
            return response('Order already processed', 200);
        }

        // 3️⃣ Extract metadata
        $userId = $session->metadata->user_id ?? null;
        $user = $userId ? User::find($userId) : null;
        $items = json_decode($session->metadata->items, true);
        $shippingAddress = json_decode($session->metadata->shippingAddress, true);
        $finalTotal = $session->metadata->final_total ?? 0;

        // 4️⃣ Create the order
        $order = Order::create([
            'user_id' => $userId,
            'total_amount' => $finalTotal,
            'payment_status' => 'paid',
            'shipping_address' => $shippingAddress,
            'payment_method' => 'stripe',
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'stripe_session_id' => $session->id,
        ]);

        // 5️⃣ Create order items and reduce stock
        foreach ($items as $item) {
            $product = Product::findOrFail($item['id']);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price' => $product->price,
                'size' => $item['size'] ?? null,
            ]);

            $product->stock -= $item['quantity'];
            $product->save();
        }

        if ($userId) {
    Cart::where('user_id', $userId)->delete();
}

        // 6️⃣ Create admin notification with product images
        $itemsPayload = collect($items)->map(function($i) {
            $product = Product::find($i['id']);
            return [
                'product_id' => $i['id'],
                'product_name' => $product->name ?? null,
                'quantity' => $i['quantity'],
                'image' => $product->images[0] ?? null, // Include product image
                'price' => $product->price ?? null,
            ];
        })->values();

        $notification = Notification::create([
            'created_by' => $userId,
            'type' => 'order.paid',
            'title' => 'Order Paid',
            'message' => "Order {$order->order_number} has been paid.",
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => $order->total_amount,
                'items' => $itemsPayload,
            ],
        ]);

        // 7️⃣ Fire event
        event(new OrderPlacedNotification($notification, $order));
    }

    // 8️⃣ Return 200 OK to Stripe
    return response('Webhook handled', 200);
}



}