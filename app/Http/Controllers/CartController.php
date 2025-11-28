<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cartItems = Cart::where('user_id', $request->user()->id)
            ->with('product')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'price' => $item->product->promo_price ?? $item->product->price,
                    'image' => $item->product->images[0] ?? null,
                    'quantity' => $item->quantity,
                    'size' => $item->size,
                    'sizes' => $item->product->sizes ?? [],
                    'size_stock' => $item->product->size_stock ?? [],
                ];
            });

        return response()->json($cartItems);
    }

    public function update(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.size' => 'nullable|string',
        ]);

        // Clear current cart
        Cart::where('user_id', $request->user()->id)->delete();

        // Add new items
        foreach ($request->items as $item) {
            Cart::create([
                'user_id' => $request->user()->id,
                'product_id' => $item['id'],
                'quantity' => $item['quantity'],
                'size' => $item['size'] ?? null,
            ]);
        }

        return response()->json(['message' => 'Cart updated successfully']);
    }

    public function delete(Request $request, $productId)
    {
        // Remove the specific item from the cart
        Cart::where('user_id', $request->user()->id)
            ->where('product_id', $productId)
            ->delete();

        return response()->json(['message' => 'Item removed from cart']);
    }

    public function clear(Request $request)
    {
        // Clear all items from the cart
        Cart::where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'Cart cleared']);
    }
}
