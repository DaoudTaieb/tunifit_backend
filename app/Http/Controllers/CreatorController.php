<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreatorController extends Controller
{
    /**
     * Get all unique creators (users who have created products)
     */
    public function index()
    {
        // Récupérer tous les créateurs uniques qui ont créé au moins un produit
        $creators = User::whereHas('createdProducts')
            ->withCount('createdProducts')
            ->get()
            ->map(function ($creator) {
                return [
                    'id' => $creator->id,
                    'first_name' => $creator->first_name,
                    'last_name' => $creator->last_name,
                    'full_name' => $creator->first_name . ' ' . $creator->last_name,
                    'email' => $creator->email,
                    'avatar' => $creator->avatar,
                    'products_count' => (int) ($creator->created_products_count ?? 0),
                ];
            });

        return response()->json([
            'creators' => $creators
        ]);
    }

    /**
     * Get all products created by a specific creator
     */
    public function show(Request $request, $id)
    {
        $creator = User::findOrFail($id);
        
        $query = Product::where('created_by', $id)
            ->with(['category', 'creator']);

        // Filter by category if provided
        if ($request->has('category')) {
            $categorySlug = $request->input('category');
            $category = \App\Models\Category::where('slug', $categorySlug)->first();
            if ($category) {
                // Get all subcategories (children) of this category
                $subcategoryIds = \App\Models\Category::where('parent_id', $category->id)
                    ->pluck('id')
                    ->toArray();
                
                // Include the category itself and all its subcategories
                $categoryIds = array_merge([$category->id], $subcategoryIds);
                
                $query->whereIn('category_id', $categoryIds);
            }
        }

        // Filter by colors
        if ($request->has('colors')) {
            $colors = explode(',', $request->colors);
            $query->whereIn('couleur', $colors);
        }
        
        // Filter by styles
        if ($request->has('styles')) {
            $styles = explode(',', $request->styles);
            $query->where(function($q) use ($styles) {
                foreach ($styles as $style) {
                    $q->orWhere('style', 'LIKE', '%' . $style . '%');
                }
            });
        }
        
        // Filter by sizes
        if ($request->has('sizes')) {
            $sizes = explode(',', $request->sizes);
            $query->where(function($q) use ($sizes) {
                foreach ($sizes as $size) {
                    $q->orWhereJsonContains('sizes', $size);
                }
            });
        }

        // Filter by price range
        if ($request->has('minPrice')) {
            $query->where('price', '>=', $request->minPrice);
        }

        if ($request->has('maxPrice')) {
            $query->where('price', '<=', $request->maxPrice);
        }

        // Filter by brands
        if ($request->has('brands')) {
            $brands = explode(',', $request->brands);
            $query->whereIn('marque', $brands);
        }

        $products = $query->paginate(12);

        return response()->json([
            'creator' => [
                'id' => $creator->id,
                'first_name' => $creator->first_name,
                'last_name' => $creator->last_name,
                'full_name' => $creator->first_name . ' ' . $creator->last_name,
                'email' => $creator->email,
                'avatar' => $creator->avatar,
            ],
            'products' => $products
        ]);
    }
}
