<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // ✅ Keep only columns that exist in your `products` table
    protected $fillable = [
        'name',
        'description',
        'price',
        'promotion_percentage',
        'promo_price',
        'stock',
        'size_stock',  // JSON object: {"S": 10, "M": 5, "L": 0, "XL": 3}
        'category_id',
        'created_by',  // user who created the product
        'images',
        'marque',   // brand
        'couleur',  // color
        'style',    // clothing style (Casual, Formel, Sport, etc.)
        'gender',   // Homme, Femme, Unisexe, Enfant
        'sizes',    // JSON array of available sizes
        'material', // fabric/material
    ];

    protected $casts = [
        'images'    => 'array',     // stored as JSON
        'sizes'     => 'array',     // stored as JSON array
        'size_stock' => 'array',    // stored as JSON object
        'price'     => 'decimal:2',
    ];

    // ----- Scopes/Helpers that match the schema -----
    public function scopeLowStock($query, $threshold = 10)
    {
        return $query->where('stock', '>', 0)
                     ->where('stock', '<=', $threshold);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock', 0);
    }

    public function isOutOfStock(): bool
    {
        return (int) $this->stock <= 0;
    }

    public function isLowStock(int $threshold = 10): bool
    {
        $s = (int) $this->stock;
        return $s > 0 && $s <= $threshold;
    }

    /**
     * Get stock status: 'instock' or 'outstock'
     */
    public function getStockStatusAttribute(): string
    {
        return $this->stock > 0 ? 'instock' : 'outstock';
    }

    /**
     * Get stock for a specific size
     */
    public function getStockForSize(string $size): int
    {
        $sizeStock = $this->size_stock ?? [];
        return (int) ($sizeStock[$size] ?? 0);
    }

    /**
     * Check if a specific size is in stock
     */
    public function isSizeInStock(string $size): bool
    {
        return $this->getStockForSize($size) > 0;
    }

    /**
     * Calculate total stock from size_stock
     */
    public function calculateTotalStockFromSizes(): int
    {
        $sizeStock = $this->size_stock ?? [];
        return array_sum(array_map('intval', $sizeStock));
    }

    // ----- Computed attributes -----
    public function getTotalSalesAttribute()
    {
        return $this->orderItems()->sum('quantity');
    }

    public function getTotalRevenueAttribute()
    {
        // sums from order_items table (quantity * price on each row)
        return $this->orderItems()->sum(\DB::raw('quantity * price'));
    }

    // ----- Relations -----
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users()
    {
        // pivot: wishlists (product_id, user_id)
        return $this->belongsToMany(User::class, 'wishlists');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    protected static function booted()
    {
        static::saving(function ($product) {
            if ($product->promotion_percentage > 0) {
                $product->promo_price = round($product->price * (1 - $product->promotion_percentage / 100), 2);
            } else {
                $product->promo_price = $product->price;
            }
        });
    }
}
