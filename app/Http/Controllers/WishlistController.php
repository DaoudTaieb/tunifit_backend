<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $wishlistItems = Wishlist::where('user_id', $request->user()->id)
            ->with('product')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'price' => $item->product->price,
                    'image' => $item->product->images[0] ?? null,
                ];
            });
            
        return response()->json($wishlistItems);
    }

    public function update(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:products,id',
        ]);

        // Clear current wishlist
        Wishlist::where('user_id', $request->user()->id)->delete();

        // Add new items
        foreach ($request->items as $item) {
            Wishlist::create([
                'user_id' => $request->user()->id,
                'product_id' => $item['id'],
            ]);
        }

        return response()->json(['message' => 'Wishlist updated successfully']);
    }

    /**
     * Remove a specific product from the wishlist
     */
    public function delete(Request $request, $productId)
    {
        // Validate product ID
        $request->validate([
            'productId' => 'exists:products,id',
        ]);

        // Delete the specific wishlist item
        Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $productId)
            ->delete();

        return response()->json(['message' => 'Item removed from wishlist']);
    }

    /**
     * Clear the entire wishlist
     */
    public function clear(Request $request)
    {
        // Delete all wishlist items for the user
        Wishlist::where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'Wishlist cleared successfully']);
    }
}
