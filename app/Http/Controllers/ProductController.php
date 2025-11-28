<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
{
    $query = Product::with(['category', 'creator']);

    if ($request->has('category')) {
        $category = Category::where('slug', $request->category)->first();
        if ($category) {
            // Get all subcategories (children) of this category
            $subcategoryIds = Category::where('parent_id', $category->id)
                ->pluck('id')
                ->toArray();
            
            // Include the category itself and all its subcategories
            $categoryIds = array_merge([$category->id], $subcategoryIds);
            
            \Log::info("Filtering products by category", [
                'category_slug' => $request->category,
                'category_id' => $category->id,
                'subcategory_ids' => $subcategoryIds,
                'all_category_ids' => $categoryIds
            ]);
            
            $query->whereIn('category_id', $categoryIds);
        }
    }

    if ($request->has('colors')) {
        $colors = explode(',', $request->colors);
        $query->whereIn('couleur', $colors);
    }
    
    // Filter by clothing style (replaces 'shapes' for eyewear)
    if ($request->has('styles')) {
        $styles = explode(',', $request->styles);
        $query->where(function($q) use ($styles) {
            foreach ($styles as $style) {
                $q->orWhere('style', 'LIKE', '%' . $style . '%');
            }
        });
    }
    
    // Filter by gender
    if ($request->has('gender')) {
        $genders = explode(',', $request->gender);
        $query->whereIn('gender', $genders);
    }
    
    // Filter by size
    if ($request->has('sizes')) {
        $sizes = explode(',', $request->sizes);
        $query->where(function($q) use ($sizes) {
            foreach ($sizes as $size) {
                $q->orWhereJsonContains('sizes', $size);
            }
        });
    }

    if ($request->has('minPrice')) {
        $query->where('price', '>=', $request->minPrice);
    }

    if ($request->has('maxPrice')) {
        $query->where('price', '<=', $request->maxPrice);
    }

    if ($request->has('brands')) {
        $brands = explode(',', $request->brands);
        $query->whereIn('marque', $brands);
    }

    // Note: On ne peut pas utiliser select() avec with() pour les relations
    // Laravel a besoin de toutes les colonnes pour charger les relations correctement
    // On charge toutes les colonnes nécessaires via with() qui inclut creator

    $products = $query->paginate(12);

    return response()->json($products);
}



    public function updatePromotion(Request $request, Product $product)
{
    $request->validate([
        'promotion_percentage' => 'required|integer|min:0|max:100'
    ]);

    $product->promotion_percentage = $request->promotion_percentage;
    $product->save(); // promo_price is auto-calculated

    return response()->json([
        'message' => 'Promotion updated',
        'product' => $product
    ]);
}


    public function show($id)
    {
        $product = Product::with(['category', 'creator'])->findOrFail($id);
        
        // Add category slug to the response
        $product->category = $product->category->slug;
        
        return response()->json($product);
    }

    public function search(Request $request)
{
    $query = $request->query('query');
    
    if (empty($query)) {
        return response()->json([]);
    }
    
    $products = Product::where('name', 'LIKE', "%{$query}%")
        ->orWhere('description', 'LIKE', "%{$query}%")
        ->get();
    
    return response()->json($products);
}
}