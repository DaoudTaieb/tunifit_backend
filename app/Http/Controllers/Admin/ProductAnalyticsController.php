<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\ProductView;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;


class ProductAnalyticsController extends Controller
{
public function incrementView(Request $request, Product $product)
{
    $user = null;
    if ($token = $request->bearerToken()) {
        $accessToken = PersonalAccessToken::findToken($token);
        if ($accessToken) {
            $user = $accessToken->tokenable;
        }
    }

    $userId = $user ? $user->id : null;

    $view = ProductView::firstOrCreate(
        ['user_id' => $userId, 'product_id' => $product->id],
        ['views' => 0, 'last_visited_at' => now()]
    );

    $view->increment('views');
    $view->update(['last_visited_at' => now()]);

    return response()->json([
        'success' => true,
        'views' => $view->views,
    ]);
}


public function totalViews()
    {
        $data = ProductView::select('product_id', DB::raw('SUM(views) as total_views'))
            ->with('product:id,name') // eager load product name
            ->groupBy('product_id')
            ->orderByDesc('total_views')
            ->get();

        return response()->json($data);
    }

    /**
     * Get top N most viewed products
     */
    public function topProducts($limit = 10)
    {
        $data = ProductView::select('product_id', DB::raw('SUM(views) as total_views'))
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('total_views')
            ->limit($limit)
            ->get();

        return response()->json($data);
    }

    /**
     * Get product views per day
     */
    public function dailyViews(Request $request)
{
    $start = Carbon::parse($request->query('start'))->startOfDay();
    $end = Carbon::parse($request->query('end'))->endOfDay();

    $views = ProductView::whereBetween('last_visited_at', [$start, $end])
        ->with('product:id,name') // adjust 'name' if needed
        ->get()
        ->groupBy(function($view) {
            return Carbon::parse($view->last_visited_at)->format('Y-m-d');
        })
        ->map(function($dayViews, $date) {
            return [
                'date' => $date,
                'total_views' => $dayViews->sum('views')
            ];
        })
        ->values();

    return response()->json($views);
}

    /**
     * Get views per user for a specific product
     */
   public function viewsByUser(Product $product)
{
    $data = ProductView::where('product_id', $product->id)
        ->with(['user:id,first_name,last_name,email'])
        ->orderByDesc('views')
        ->get()
        ->map(function ($view) {
            if ($view->user) {
                // Authenticated user
                $view->user->full_name = trim($view->user->first_name . ' ' . $view->user->last_name);
            } else {
                // Visitor
                $view->user = (object)[
                    'full_name' => 'Visitor',
                    'email' => null,
                ];
            }
            return $view;
        });

    return response()->json($data);
}



   public function totalViewsOverview()
{
    $totalViews = ProductView::sum('views');
    $totalProductsViewed = ProductView::distinct('product_id')->count('product_id');
    $totalUsersViewed = ProductView::whereNotNull('user_id')->distinct('user_id')->count('user_id');

    return response()->json([
        'total_views' => $totalViews,
        'total_products_viewed' => $totalProductsViewed,
        'total_users_viewed' => $totalUsersViewed, // counts only real users
    ]);
}


}