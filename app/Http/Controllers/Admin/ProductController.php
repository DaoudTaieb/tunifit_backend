<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $category = $request->input('category');
        $user = $request->user();
    
        $products = Product::with(['category', 'creator']);

        // Filter by creator if user is not super_admin
        if ($user && $user->role !== 'super_admin') {
            $products->where('created_by', $user->id);
        }
        
        return response()->json([
            'products' => $products->get()
        ]);
    }

    public function show($id)
    {
        $user = request()->user();
        $query = Product::with(['category', 'orderItems']);

        // Filter by creator if user is not super_admin
        if ($user && $user->role !== 'super_admin') {
            $query->where('created_by', $user->id);
        }

        $product = $query->findOrFail($id);
        
        $analytics = [
            'total_sales' => $product->total_sales,
            'total_revenue' => $product->total_revenue,
            'views_count' => 0, // This would need to be tracked separately
            'wishlist_count' => $product->users()->count(),
        ];

        return response()->json([
            'product' => $product,
            'analytics' => $analytics,
        ]);
    }

    public function store(Request $request)
{
    // Enums for clothing
    $marques = ['Nike','Adidas','Zara','H&M','Uniqlo','Gap','Levi\'s','Calvin Klein','Tommy Hilfiger','Ralph Lauren','Puma','Polo','Lacoste','Guess','Forever 21'];
    $styles  = ['Casual', 'Formel', 'Sport', 'Élégant', 'Décontracté', 'Chic', 'Vintage', 'Moderne'];
    $genders = ['Homme', 'Femme', 'Unisexe', 'Enfant'];
    // Tailles acceptées : standard (vêtements) + numériques (pantalons, chaussures)
    $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '30', '32', '34', '36', '38', '39', '40', '41', '42', '43', '44', '45'];

    $request->validate([
        'name'        => 'required|string|max:255',
        'description' => 'required|string',
        'price'       => 'required|numeric|min:0',
        'stock'       => 'required|integer|min:0',
        'category'    => 'required|string|exists:categories,slug',
        'images'      => 'required|array|min:1',
        'images.*'    => 'string', // url or base64
        'marque'      => 'required|in:' . implode(',', $marques),
        'couleur'     => 'nullable|string|max:50',
        'style'       => 'nullable|in:' . implode(',', $styles),
        'gender'      => 'nullable|in:' . implode(',', $genders),
        'sizes'       => 'nullable|array',
        'sizes.*'     => 'in:' . implode(',', $sizes),
        'size_stock'  => 'nullable|array',
        'size_stock.*' => 'integer|min:0',
        'material'    => 'nullable|string|max:100',
    ]);

    $category = Category::where('slug', $request->category)->firstOrFail();

    // Calculate total stock from size_stock if provided, otherwise use stock
    $totalStock = $request->stock;
    if ($request->has('size_stock') && is_array($request->size_stock)) {
        $totalStock = array_sum(array_map('intval', $request->size_stock));
    }

    $product = Product::create([
        'name'         => $request->name,
        'description'  => $request->description,
        'price'        => $request->price,
        'stock'        => $totalStock,
        'size_stock'   => $request->size_stock ?? null,
        'category_id'  => $category->id,
        'created_by'   => $request->user()->id, // Enregistrer l'utilisateur qui crée le produit
        'images'       => $request->images,
        'marque'       => $request->marque,
        'couleur'      => $request->couleur,
        'style'        => $request->style,
        'gender'       => $request->gender ?? 'Unisexe',
        'sizes'        => $request->sizes ?? [],
        'material'     => $request->material,
    ]);

    return response()->json([
        'message' => 'Product created successfully',
        'product' => $product->load('category'),
    ], 201);
}


    public function update(Request $request, $id)
{
    $product = Product::findOrFail($id);

    // Enums for clothing
    $marques = ['Nike','Adidas','Zara','H&M','Uniqlo','Gap','Levi\'s','Calvin Klein','Tommy Hilfiger','Ralph Lauren','Puma','Polo','Lacoste','Guess','Forever 21'];
    $styles  = ['Casual', 'Formel', 'Sport', 'Élégant', 'Décontracté', 'Chic', 'Vintage', 'Moderne'];
    $genders = ['Homme', 'Femme', 'Unisexe', 'Enfant'];
    // Tailles acceptées : standard (vêtements) + numériques (pantalons, chaussures)
    $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '30', '32', '34', '36', '38', '39', '40', '41', '42', '43', '44', '45'];

    // Validate only fields that may be sent (partial update)
    $validated = $request->validate([
        'name'         => 'sometimes|required|string|max:255',
        'description'  => 'sometimes|required|string',
        'price'        => 'sometimes|required|numeric|min:0',
        'stock'        => 'sometimes|required|integer|min:0',
        'category'     => 'sometimes|required|string|exists:categories,slug',
        'images'       => 'sometimes|required|array|min:1',
        'images.*'     => 'string',        // URLs or base64 strings
        'marque'       => 'sometimes|required|in:' . implode(',', $marques),
        'couleur'      => 'nullable|string|max:50',
        'style'        => 'nullable|in:' . implode(',', $styles),
        'gender'       => 'nullable|in:' . implode(',', $genders),
        'sizes'        => 'nullable|array',
        'sizes.*'      => 'in:' . implode(',', $sizes),
        'size_stock'   => 'nullable|array',
        'size_stock.*' => 'integer|min:0',
        'material'     => 'nullable|string|max:100',
    ]);

    // Build the data to update
    $data = $request->only([
        'name',
        'description',
        'price',
        'stock',
        'images',
        'marque',
        'couleur',
        'style',
        'gender',
        'sizes',
        'size_stock',
        'material',
    ]);

    // Calculate total stock from size_stock if provided
    if ($request->has('size_stock') && is_array($request->size_stock)) {
        $data['stock'] = array_sum(array_map('intval', $request->size_stock));
    }

    // Map category slug -> category_id if provided
    if ($request->filled('category')) {
        $category = Category::where('slug', $request->category)->firstOrFail();
        $data['category_id'] = $category->id;
    }

    // If Product model casts ['images' => 'array'], you can assign the array directly
    // Eloquent will JSON-encode it on save.
    $product->update($data);

    return response()->json([
        'message' => 'Product updated successfully',
        'product' => $product->load('category'),
    ]);
}


    public function destroy($id)
    {
        $user = request()->user();
        $product = Product::findOrFail($id);

        // Check if creator can only delete their own products
        if ($user && $user->role !== 'super_admin' && $product->created_by !== $user->id) {
            return response()->json(['message' => 'Unauthorized. You can only delete your own products.'], 403);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    

    public function updateStock(Request $request, $id)
    {
        $request->validate([
            'stock' => 'required|integer|min:0',
            'operation' => 'sometimes|in:set,add,subtract',
        ]);

        $product = Product::findOrFail($id);
        $operation = $request->operation ?? 'set';

        switch ($operation) {
            case 'add':
                $product->stock += $request->stock;
                break;
            case 'subtract':
                $product->stock = max(0, $product->stock - $request->stock);
                break;
            default:
                $product->stock = $request->stock;
        }

        $product->save();

        return response()->json([
            'message' => 'Stock updated successfully',
            'product' => $product,
        ]);
    }

public function getStats(Request $request)
{
    $user = $request->user();
    $lowStockThreshold = 5;

    $query = Product::query();

    // Filter by creator if user is not super_admin
    if ($user && $user->role !== 'super_admin') {
        $query->where('created_by', $user->id);
    }

    $totalProducts = (clone $query)->count();

    // Active / Inactive
    if (Schema::hasColumn('products', 'status')) {
        $activeProducts   = (clone $query)->where('status', 'active')->count();
        $inactiveProducts = (clone $query)->where('status', '!=', 'active')->orWhereNull('status')->count();
    } elseif (Schema::hasColumn('products', 'is_active')) {
        $activeProducts   = (clone $query)->where('is_active', 1)->count();
        $inactiveProducts = (clone $query)->where('is_active', 0)->orWhereNull('is_active')->count();
    } elseif (Schema::hasColumn('products', 'published')) {
        $activeProducts   = (clone $query)->where('published', 1)->count();
        $inactiveProducts = (clone $query)->where('published', 0)->orWhereNull('published')->count();
    } else {
        $activeProducts = $totalProducts;  // no activation flag → treat all as active
        $inactiveProducts = 0;
    }

    // Stock-related
    if (Schema::hasColumn('products', 'stock')) {
        $outOfStock = (clone $query)->where(function ($q) {
                $q->where('stock', '<=', 0)->orWhereNull('stock');
            })->count();

        $lowStock = (clone $query)->where('stock', '>', 0)
            ->where('stock', '<=', $lowStockThreshold)
            ->count();
    } else {
        $outOfStock = 0;
        $lowStock = 0;
    }

    // Featured
    if (Schema::hasColumn('products', 'featured')) {
        $featuredProducts = (clone $query)->where('featured', 1)->count();
    } elseif (Schema::hasColumn('products', 'is_featured')) {
        $featuredProducts = (clone $query)->where('is_featured', 1)->count();
    } else {
        $featuredProducts = 0;
    }

    // Total inventory value
    if (Schema::hasColumn('products', 'price') && Schema::hasColumn('products', 'stock')) {
        $totalValue = (clone $query)->sum(DB::raw('price * stock'));
    } else {
        $totalValue = 0;
    }

    // Categories count - filter by creator if not super_admin
    $categoryQuery = Category::query();
    if ($user && $user->role !== 'super_admin') {
        $categoryQuery->where('created_by', $user->id);
    }

    $stats = [
        'total_products'    => $totalProducts,
        'active_products'   => $activeProducts,
        'inactive_products' => $inactiveProducts,
        'out_of_stock'      => $outOfStock,
        'low_stock'         => $lowStock,
        'featured_products' => $featuredProducts,
        'total_value'       => $totalValue,
        'categories_count'  => $categoryQuery->count(),
    ];

    return response()->json(['stats' => $stats]);
}


    private function generateSku($name)
    {
        $base = strtoupper(Str::slug($name, ''));
        $base = substr($base, 0, 6);
        $suffix = str_pad(Product::count() + 1, 4, '0', STR_PAD_LEFT);
        
        return $base . $suffix;
    }


}
