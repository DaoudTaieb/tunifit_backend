<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;
class CategoryController extends Controller
{
    

public function index(Request $request)
{
    $search     = $request->input('search');
    $sortBy     = $request->input('sort_by', 'id');
    $sortOrder  = $request->input('sort_order', 'asc');
    $user = $request->user();

    $query = Category::query();

    // Filter by creator if user is not super_admin
    if ($user && $user->role !== 'super_admin') {
        $query->where('created_by', $user->id);
    }

    // Search on name/description
    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // Sort (fallback on id if column doesn't exist)
    if (!\Schema::hasColumn('categories', $sortBy)) {
        $sortBy = 'id';
    }

    $categories = $query
    ->with(['creator']) // Include creator relation for super admin
    ->withCount('products') 
    ->orderBy($sortBy, $sortOrder)
    ->get();

    return response()->json([
        'categories' => $categories,
        'total'      => $categories->count(),
    ]);
}


    public function show($id)
{
    $user = request()->user();
    $query = Category::with('products')
        ->withCount('products'); // no activeProducts count here

    // Filter by creator if user is not super_admin
    if ($user && $user->role !== 'super_admin') {
        $query->where('created_by', $user->id);
    }

    $category = $query->findOrFail($id);

    // Prevent Laravel from calling accessors in $appends (like active_products_count)
    $category->setAppends([]);         // remove all appended attributes for this instance
    // or: $category->makeHidden(['active_products_count']);

    return response()->json(['category' => $category]);
}

    public function store(Request $request)
{
    $request->validate([
        'name'        => 'required|string|max:255',
        'slug'        => 'nullable|string|max:255|unique:categories,slug',
        'description' => 'nullable|string',
        'image'       => 'nullable|string',
        'status'      => 'sometimes|in:active,inactive',
    ]);

    // Generate slug (or use provided) and ensure uniqueness
    $slug = $request->filled('slug') ? $request->slug : Str::slug($request->name);
    $base = $slug;
    $i = 1;
    while (Category::where('slug', $slug)->exists()) {
        $slug = "{$base}-{$i}";
        $i++;
    }

    $category = Category::create([
        'name'        => $request->name,
        'slug'        => $slug,
        'description' => $request->description,
        'image'       => $request->image,
        'status'      => $request->input('status', 'active'),
        'created_by'  => $request->user()->id, // Enregistrer l'utilisateur qui crée la catégorie
    ]);

    return response()->json([
        'message'  => 'Category created successfully',
        'category' => $category, // no parent/children (no parent_id in schema)
    ], 201);
}

    public function update(Request $request, $id)
{
    $user = $request->user();
    $category = Category::findOrFail($id);

    // Check if creator can only update their own categories
    if ($user && $user->role !== 'super_admin' && $category->created_by !== $user->id) {
        return response()->json(['message' => 'Unauthorized. You can only update your own categories.'], 403);
    }

    $request->validate([
        'name'        => 'required|string|max:255',
        'slug'        => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($id)],
        'description' => 'nullable|string',
        'image'       => 'nullable|string',
        'status'      => 'sometimes|in:active,inactive',
    ]);

    // Generate/normalize slug and ensure uniqueness (excluding this ID)
    $slug = $request->filled('slug') ? $request->slug : Str::slug($request->name);
    $base = $slug;
    $i = 1;
    while (
        Category::where('slug', $slug)
            ->where('id', '!=', $id)
            ->exists()
    ) {
        $slug = "{$base}-{$i}";
        $i++;
    }

    $category->update([
        'name'        => $request->name,
        'slug'        => $slug,
        'description' => $request->description,
        'image'       => $request->image,
        'status'      => $request->input('status', $category->status),
    ]);

    return response()->json([
        'message'  => 'Category updated successfully',
        'category' => $category, // no parent/children in schema
    ]);
}


    public function destroy($id)
{
    $user = request()->user();
    $category = Category::findOrFail($id);

    // Check if creator can only delete their own categories
    if ($user && $user->role !== 'super_admin' && $category->created_by !== $user->id) {
        return response()->json(['message' => 'Unauthorized. You can only delete your own categories.'], 403);
    }

    // Your schema has no parent/children columns, so skip children checks.
    // Block deletion if products exist in this category.
    $hasProducts = Product::where('category_id', $category->id)->exists();
    if ($hasProducts) {
        return response()->json([
            'message' => 'Cannot delete category with products. Please move or delete products first.'
        ], 422);
    }

    $category->delete();

    return response()->json(['message' => 'Category deleted successfully']);
}


    
    public function reorder(Request $request)
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|integer|exists:categories,id',
            'categories.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->categories as $categoryData) {
            Category::where('id', $categoryData['id'])
                ->update(['sort_order' => $categoryData['sort_order']]);
        }

        return response()->json(['message' => 'Categories reordered successfully']);
    }

    public function getStats(Request $request)
    {
        $user = $request->user();
        $query = Category::query();

        // Filter by creator if user is not super_admin
        if ($user && $user->role !== 'super_admin') {
            $query->where('created_by', $user->id);
        }

        $stats = [
            'total_categories' => (clone $query)->count(),
            'active_categories' => (clone $query)->active()->count(),
            'categories_with_products' => (clone $query)->has('products')->count(),
            'empty_categories' => (clone $query)->doesntHave('products')->count(),
        ];

        return response()->json(['stats' => $stats]);
    }

    private function wouldCreateCircularReference($categoryId, $parentId)
    {
        $parent = Category::find($parentId);
        
        while ($parent) {
            if ($parent->id == $categoryId) {
                return true;
            }
            $parent = $parent->parent;
        }
        
        return false;
    }

    public function updateBottomBanner(Request $request, $id)
    {
        // Only super_admin can update category banners
        if ($request->user()->role !== 'super_admin') {
            return response()->json(['message' => 'Unauthorized. Only super admin can update category banners.'], 403);
        }

        $category = Category::findOrFail($id);

        $request->validate([
            'bottom_banner_title' => 'nullable|string|max:255',
            'bottom_banner_description' => 'nullable|string',
            'bottom_banner_button_text' => 'nullable|string|max:255',
            'bottom_banner_button_link' => 'nullable|url|max:500',
            'bottom_banner_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        $data = [
            'bottom_banner_title' => $request->input('bottom_banner_title'),
            'bottom_banner_description' => $request->input('bottom_banner_description'),
            'bottom_banner_button_text' => $request->input('bottom_banner_button_text'),
            'bottom_banner_button_link' => $request->input('bottom_banner_button_link'),
        ];

        // Handle image upload
        if ($request->hasFile('bottom_banner_image')) {
            $image = $request->file('bottom_banner_image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->storeAs('public', $imageName);
            $data['bottom_banner_image'] = $imageName;
        }

        $category->update($data);

        return response()->json([
            'message' => 'Category bottom banner updated successfully',
            'category' => $category->fresh(),
        ]);
    }
}
