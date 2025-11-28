<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get orders query filtered by creator if needed
     */
    private function getOrdersQuery()
    {
        $user = request()->user();
        $query = Order::query();
        
        // Filter orders by creator's products if user is not super_admin
        if ($user && $user->role !== 'super_admin') {
            $query->whereHas('items.product', function($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        }
        
        return $query;
    }

    /**
     * Get products query filtered by creator if needed
     */
    private function getProductsQuery()
    {
        $user = request()->user();
        $query = Product::query();
        
        // Filter products by creator if user is not super_admin
        if ($user && $user->role !== 'super_admin') {
            $query->where('created_by', $user->id);
        }
        
        return $query;
    }

    /**
     * Get revenue from orders containing creator's products
     */
    private function getRevenueQuery()
    {
        $user = request()->user();
        $query = Order::delivered();
        
        // Filter orders by creator's products if user is not super_admin
        if ($user && $user->role !== 'super_admin') {
            // Calculate revenue only from order items that belong to creator's products
            return OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('orders.status', 'delivered')
                ->where('products.created_by', $user->id)
                ->selectRaw('SUM(order_items.quantity * order_items.price) as total');
        }
        
        return $query;
    }

    public function overview()
    {
        $user = request()->user();
        $isSuperAdmin = $user && $user->role === 'super_admin';
        
        // Revenue calculation - for creators, calculate only from their products
        if ($isSuperAdmin) {
            $totalRevenue = Order::delivered()->sum('total_amount');
            $todayRevenue = Order::delivered()->whereDate('created_at', today())->sum('total_amount');
            $monthRevenue = Order::delivered()->whereMonth('created_at', now()->month)->sum('total_amount');
        } else {
            // For creators, calculate revenue from order items of their products
            $totalRevenue = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('orders.status', 'delivered')
                ->where('products.created_by', $user->id)
                ->sum(DB::raw('order_items.quantity * order_items.price'));
            
            $todayRevenue = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('orders.status', 'delivered')
                ->where('products.created_by', $user->id)
                ->whereDate('orders.created_at', today())
                ->sum(DB::raw('order_items.quantity * order_items.price'));
            
            $monthRevenue = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('orders.status', 'delivered')
                ->where('products.created_by', $user->id)
                ->whereMonth('orders.created_at', now()->month)
                ->sum(DB::raw('order_items.quantity * order_items.price'));
        }

        $ordersQuery = $this->getOrdersQuery();
        $productsQuery = $this->getProductsQuery();

        $stats = [
            // Core metrics
            'total_revenue' => $totalRevenue ?? 0,
            'total_orders' => $ordersQuery->count(),
            'total_customers' => $isSuperAdmin ? User::customers()->count() : 0, // Creators don't see customers
            'total_products' => $productsQuery->count(),
            
            // Today's metrics
            'today_revenue' => $todayRevenue ?? 0,
            'today_orders' => (clone $ordersQuery)->whereDate('created_at', today())->count(),
            'today_customers' => $isSuperAdmin ? User::whereDate('created_at', today())->count() : 0,
            
            // This month's metrics
            'month_revenue' => $monthRevenue ?? 0,
            'month_orders' => (clone $ordersQuery)->whereMonth('created_at', now()->month)->count(),
            'month_customers' => $isSuperAdmin ? User::whereMonth('created_at', now()->month)->count() : 0,
            
            // Growth metrics (compared to last month)
            'revenue_growth' => $this->calculateGrowth('revenue'),
            'orders_growth' => $this->calculateGrowth('orders'),
            'customers_growth' => $isSuperAdmin ? $this->calculateGrowth('customers') : 0,
            
            // Business health metrics
            'average_order_value' => $isSuperAdmin 
                ? Order::delivered()->avg('total_amount') 
                : ($ordersQuery->where('status', 'delivered')->count() > 0 
                    ? $totalRevenue / $ordersQuery->where('status', 'delivered')->count() 
                    : 0),
            'conversion_rate' => $isSuperAdmin ? $this->calculateConversionRate() : 0,
            'customer_lifetime_value' => $isSuperAdmin ? $this->calculateCustomerLifetimeValue() : 0,
            'pending_orders' => (clone $ordersQuery)->where('status', 'pending')->count(),
            'low_stock_products' => (clone $productsQuery)->lowStock()->count(),
            'out_of_stock_products' => (clone $productsQuery)->outOfStock()->count(),
        ];

        return response()->json(['overview' => $stats]);
    }

    public function revenueAnalytics(Request $request)
    {
        $period = $request->input('period', 'month'); // week, month, quarter, year
        $comparison = $request->input('comparison', true);

        $analytics = [
            'current_period' => $this->getRevenuePeriodData($period),
            'chart_data' => $this->getRevenueChartData($period),
        ];

        if ($comparison) {
            $analytics['previous_period'] = $this->getRevenuePeriodData($period, true);
            $analytics['growth_percentage'] = $this->calculatePeriodGrowth($period);
        }

        return response()->json(['revenue_analytics' => $analytics]);
    }

    public function salesAnalytics(Request $request)
{
    $user = request()->user();
    $isSuperAdmin = $user && $user->role === 'super_admin';
    $period = $request->input('period', 'month'); // day|week|month|year

    $ordersQuery = $this->getOrdersQuery();
    $productsQuery = $this->getProductsQuery();

    // Sales by status - filtered by creator's orders
    $salesByStatusQuery = Order::selectRaw('status, COUNT(*) as count, SUM(total_amount) as revenue');
    if (!$isSuperAdmin) {
        $salesByStatusQuery->whereHas('items.product', function($q) use ($user) {
            $q->where('created_by', $user->id);
        });
    }
    $salesByStatus = $salesByStatusQuery->groupBy('status')->get();

    // Sales by payment method - filtered by creator's orders
    $salesByPaymentQuery = Order::selectRaw('payment_method, COUNT(*) as count, SUM(total_amount) as revenue');
    if (!$isSuperAdmin) {
        $salesByPaymentQuery->whereHas('items.product', function($q) use ($user) {
            $q->where('created_by', $user->id);
        });
    }
    $salesByPayment = $salesByPaymentQuery->groupBy('payment_method')->get();

    // Top selling products - only creator's products
    $topProductsQuery = OrderItem::join('products', 'order_items.product_id', '=', 'products.id')
        ->selectRaw('products.id, products.name, SUM(order_items.quantity) as total_quantity, SUM(order_items.quantity * order_items.price) as revenue')
        ->groupBy('products.id', 'products.name');
    if (!$isSuperAdmin) {
        $topProductsQuery->where('products.created_by', $user->id);
    }
    $topProducts = $topProductsQuery->orderByDesc('revenue')->limit(10)->get();

    // Top categories - only creator's categories
    $topCategoriesQuery = OrderItem::join('products', 'order_items.product_id', '=', 'products.id')
        ->join('categories', 'products.category_id', '=', 'categories.id')
        ->selectRaw('categories.id, categories.name, SUM(order_items.quantity) as total_quantity, SUM(order_items.quantity * order_items.price) as revenue')
        ->groupBy('categories.id', 'categories.name');
    if (!$isSuperAdmin) {
        $topCategoriesQuery->where('products.created_by', $user->id);
    }
    $topCategories = $topCategoriesQuery->orderByDesc('revenue')->limit(10)->get();

    $analytics = [
        'sales_by_status' => $salesByStatus,
        'sales_by_payment_method' => $salesByPayment,
        'top_selling_products' => $topProducts,
        'top_categories' => $topCategories,
        'sales_trends' => $this->getSalesTrends($period),
        'hourly_sales' => $this->getHourlySales(),
        'daily_sales'  => $this->getDailySales(),
    ];

    return response()->json(['sales_analytics' => $analytics]);
}


    public function customerAnalytics()
    {
        $analytics = [
            'customer_segments' => [
                'new_customers' => User::customers()->where('created_at', '>=', now()->subDays(30))->count(),
                'returning_customers' => User::customers()->has('orders', '>', 1)->count(),
                'vip_customers' => User::customers()->withSum('orders as total_spent', 'total_amount')
                    ->having('total_spent', '>', 1000)->count(),
            ],
            
            'customer_behavior' => [
                'average_orders_per_customer' => round(Order::count() / User::customers()->count(), 2),
                'repeat_purchase_rate' => $this->calculateRepeatPurchaseRate(),
                'customer_retention_rate' => $this->calculateCustomerRetentionRate(),
            ],
            
            'geographic_distribution' => $this->getGeographicDistribution(),
            'customer_acquisition' => $this->getCustomerAcquisitionData(),
            'top_customers' => $this->getTopCustomers(),
        ];

        return response()->json(['customer_analytics' => $analytics]);
    }

public function productAnalytics()
{
    $user = request()->user();
    $isSuperAdmin = $user && $user->role === 'super_admin';
    $lowStockThreshold = 5;

    $productsQuery = $this->getProductsQuery();

    // ====== Comptages robustes sans scopes ======
    $totalProducts = (clone $productsQuery)->count();

    // "active_products" : on essaie status / is_active / published, sinon fallback = total
    if (Schema::hasColumn('products', 'status')) {
        $activeProducts = (clone $productsQuery)->where('status', 'active')->count();
    } elseif (Schema::hasColumn('products', 'is_active')) {
        $activeProducts = (clone $productsQuery)->where('is_active', 1)->count();
    } elseif (Schema::hasColumn('products', 'published')) {
        $activeProducts = (clone $productsQuery)->where('published', 1)->count();
    } else {
        // aucun indicateur d'activation → on ne filtre pas
        $activeProducts = $totalProducts;
    }

    // "stock" : si la colonne n'existe pas, on renvoie 0 pour low/out-of-stock
    if (Schema::hasColumn('products', 'stock')) {
        $outOfStock = (clone $productsQuery)->where(function($q){
                $q->where('stock', '<=', 0)->orWhereNull('stock');
            })->count();

        $lowStock = (clone $productsQuery)->where('stock', '>', 0)
            ->where('stock', '<=', $lowStockThreshold)
            ->count();
    } else {
        $outOfStock = 0;
        $lowStock = 0;
    }

    // Valeur d'inventaire : nécessite price & stock
    if (Schema::hasColumn('products', 'price') && Schema::hasColumn('products', 'stock')) {
        $totalInventoryValue = (clone $productsQuery)->sum(DB::raw('price * stock'));
    } else {
        $totalInventoryValue = 0;
    }

    // ====== Performances produits (garde tes helpers si tu veux) ======
    $analytics = [
        'inventory_status' => [
            'total_products'        => $totalProducts,
            'active_products'       => $activeProducts,
            'low_stock'             => $lowStock,
            'out_of_stock'          => $outOfStock,
            'total_inventory_value' => $totalInventoryValue,
        ],

        'product_performance' => [
            'best_sellers'     => $this->getBestSellingProducts(),
            'worst_performers' => $this->getWorstPerformingProducts(),
            'most_viewed'      => $this->getMostViewedProducts(),
            'most_wishlisted'  => $this->getMostWishlistedProducts(),
        ],

        'category_performance' => $this->getCategoryPerformance(),
        'price_analysis'       => $this->getPriceAnalysis(),
        'stock_alerts'         => $this->getStockAlerts(),
    ];

    return response()->json(['product_analytics' => $analytics]);
}


public function realTimeStats()
{
    $user = request()->user();
    $isSuperAdmin = $user && $user->role === 'super_admin';
    
    // Fenêtre d'« activité récente » pour considérer un user en ligne
    $activeWindowMinutes = 10;
    $since = Carbon::now()->subMinutes($activeWindowMinutes);

    // Détection robuste des "online_users" selon les colonnes/tables présentes
    $onlineUsersCount = 0;

    if ($isSuperAdmin) {
        if (Schema::hasColumn('users', 'last_seen_at')) {
            $onlineUsersCount = \App\Models\User::where('last_seen_at', '>=', $since)->count();
        } elseif (Schema::hasColumn('users', 'updated_at')) {
            $onlineUsersCount = \App\Models\User::where('updated_at', '>=', $since)->count();
        } elseif (Schema::hasTable('personal_access_tokens') && Schema::hasColumn('personal_access_tokens', 'last_used_at')) {
            $onlineUsersCount = DB::table('personal_access_tokens')
                ->where('last_used_at', '>=', $since)
                ->distinct('tokenable_id')
                ->count('tokenable_id');
        } elseif (Schema::hasTable('sessions')) {
            $onlineUsersCount = DB::table('sessions')
                ->where('last_activity', '>=', Carbon::now()->subMinutes($activeWindowMinutes)->timestamp)
                ->distinct('user_id')
                ->whereNotNull('user_id')
                ->count('user_id');
        }
    }

    $ordersQuery = $this->getOrdersQuery();
    $productsQuery = $this->getProductsQuery();

    // Recent orders - filtered by creator
    $recentOrdersQuery = \App\Models\Order::with(['user', 'items']);
    if (!$isSuperAdmin) {
        $recentOrdersQuery->whereHas('items.product', function($q) use ($user) {
            $q->where('created_by', $user->id);
        });
    }

    // Live revenue - filtered by creator
    if ($isSuperAdmin) {
        $liveRevenue = \App\Models\Order::whereDate('created_at', today())->sum('total_amount');
    } else {
        $liveRevenue = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.created_by', $user->id)
            ->whereDate('orders.created_at', today())
            ->sum(DB::raw('order_items.quantity * order_items.price'));
    }

    $stats = [
        'online_users' => $onlineUsersCount,
        'recent_orders' => $recentOrdersQuery->latest()->limit(10)->get(),
        'recent_customers' => $isSuperAdmin 
            ? \App\Models\User::customers()->latest()->limit(10)->get() 
            : [],
        'live_revenue' => $liveRevenue ?? 0,
        'pending_orders_count' => (clone $ordersQuery)->where('status', 'pending')->count(),
        'processing_orders_count' => (clone $ordersQuery)->where('status', \App\Models\Order::STATUS_PROCESSING)->count(),
        'low_stock_alerts' => (clone $productsQuery)->lowStock(5)->count(),
    ];

    return response()->json(['real_time_stats' => $stats]);
}



    // Helper methods for calculations
    private function calculateGrowth($metric)
    {
        $user = request()->user();
        $isSuperAdmin = $user && $user->role === 'super_admin';
        $currentMonth = now()->month;
        $lastMonth = now()->subMonth()->month;

        switch ($metric) {
            case 'revenue':
                if ($isSuperAdmin) {
                    $current = Order::delivered()->whereMonth('created_at', $currentMonth)->sum('total_amount');
                    $previous = Order::delivered()->whereMonth('created_at', $lastMonth)->sum('total_amount');
                } else {
                    $current = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->join('products', 'order_items.product_id', '=', 'products.id')
                        ->where('orders.status', 'delivered')
                        ->where('products.created_by', $user->id)
                        ->whereMonth('orders.created_at', $currentMonth)
                        ->sum(DB::raw('order_items.quantity * order_items.price'));
                    
                    $previous = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->join('products', 'order_items.product_id', '=', 'products.id')
                        ->where('orders.status', 'delivered')
                        ->where('products.created_by', $user->id)
                        ->whereMonth('orders.created_at', $lastMonth)
                        ->sum(DB::raw('order_items.quantity * order_items.price'));
                }
                break;
            case 'orders':
                $ordersQuery = $this->getOrdersQuery();
                $current = (clone $ordersQuery)->whereMonth('created_at', $currentMonth)->count();
                $previous = (clone $ordersQuery)->whereMonth('created_at', $lastMonth)->count();
                break;
            case 'customers':
                if ($isSuperAdmin) {
                    $current = User::customers()->whereMonth('created_at', $currentMonth)->count();
                    $previous = User::customers()->whereMonth('created_at', $lastMonth)->count();
                } else {
                    return 0; // Creators don't see customer growth
                }
                break;
            default:
                return 0;
        }

        if ($previous == 0) return $current > 0 ? 100 : 0;
        return round((($current - $previous) / $previous) * 100, 2);
    }

    private function calculateConversionRate()
    {
        $totalUsers = User::customers()->count();
        $usersWithOrders = User::customers()->has('orders')->count();
        
        return $totalUsers > 0 ? round(($usersWithOrders / $totalUsers) * 100, 2) : 0;
    }

    private function calculateCustomerLifetimeValue()
{
    // Average of per-customer lifetime spend (sum of their orders' total_amount)
    return User::query()
        ->customers()
        ->leftJoinSub(
            DB::table('orders')
                ->select('user_id', DB::raw('SUM(total_amount) AS total_spent'))
                ->groupBy('user_id'),
            'u_totals',
            'u_totals.user_id',
            '=',
            'users.id'
        )
        ->avg(DB::raw('COALESCE(u_totals.total_spent, 0)')) ?? 0;
}


    private function getRevenuePeriodData($period, $previous = false)
    {
        $user = request()->user();
        $isSuperAdmin = $user && $user->role === 'super_admin';
        
        switch ($period) {
            case 'week':
                $start = $previous ? now()->subWeeks(2)->startOfWeek() : now()->startOfWeek();
                $end = $previous ? now()->subWeek()->endOfWeek() : now()->endOfWeek();
                break;
            case 'month':
                $start = $previous ? now()->subMonths(2)->startOfMonth() : now()->startOfMonth();
                $end = $previous ? now()->subMonth()->endOfMonth() : now()->endOfMonth();
                break;
            case 'quarter':
                $start = $previous ? now()->subQuarters(2)->startOfQuarter() : now()->startOfQuarter();
                $end = $previous ? now()->subQuarter()->endOfQuarter() : now()->endOfQuarter();
                break;
            case 'year':
                $start = $previous ? now()->subYears(2)->startOfYear() : now()->startOfYear();
                $end = $previous ? now()->subYear()->endOfYear() : now()->endOfYear();
                break;
        }

        if ($isSuperAdmin) {
            $revenue = Order::delivered()->whereBetween('created_at', [$start, $end])->sum('total_amount');
            $orders = Order::whereBetween('created_at', [$start, $end])->count();
        } else {
            $revenue = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('orders.status', 'delivered')
                ->where('products.created_by', $user->id)
                ->whereBetween('orders.created_at', [$start, $end])
                ->sum(DB::raw('order_items.quantity * order_items.price'));
            
            $orders = Order::whereHas('items.product', function($q) use ($user) {
                    $q->where('created_by', $user->id);
                })
                ->whereBetween('created_at', [$start, $end])
                ->count();
        }

        return [
            'revenue' => $revenue ?? 0,
            'orders' => $orders ?? 0,
            'period_start' => $start,
            'period_end' => $end,
        ];
    }

    private function getRevenueChartData($period)
    {
        $user = request()->user();
        $isSuperAdmin = $user && $user->role === 'super_admin';

        if ($isSuperAdmin) {
            switch ($period) {
                case 'week':
                    return Order::delivered()
                        ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
                        ->whereBetween('created_at', [now()->subDays(7), now()])
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get();
                case 'month':
                    return Order::delivered()
                        ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
                        ->whereBetween('created_at', [now()->subDays(30), now()])
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get();
                case 'year':
                    return Order::delivered()
                        ->selectRaw('MONTH(created_at) as month, SUM(total_amount) as revenue')
                        ->whereYear('created_at', now()->year)
                        ->groupBy('month')
                        ->orderBy('month')
                        ->get();
            }
        } else {
            // For creators, calculate revenue from order items
            switch ($period) {
                case 'week':
                    return OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->join('products', 'order_items.product_id', '=', 'products.id')
                        ->where('orders.status', 'delivered')
                        ->where('products.created_by', $user->id)
                        ->whereBetween('orders.created_at', [now()->subDays(7), now()])
                        ->selectRaw('DATE(orders.created_at) as date, SUM(order_items.quantity * order_items.price) as revenue')
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get();
                case 'month':
                    return OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->join('products', 'order_items.product_id', '=', 'products.id')
                        ->where('orders.status', 'delivered')
                        ->where('products.created_by', $user->id)
                        ->whereBetween('orders.created_at', [now()->subDays(30), now()])
                        ->selectRaw('DATE(orders.created_at) as date, SUM(order_items.quantity * order_items.price) as revenue')
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get();
                case 'year':
                    return OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->join('products', 'order_items.product_id', '=', 'products.id')
                        ->where('orders.status', 'delivered')
                        ->where('products.created_by', $user->id)
                        ->whereYear('orders.created_at', now()->year)
                        ->selectRaw('MONTH(orders.created_at) as month, SUM(order_items.quantity * order_items.price) as revenue')
                        ->groupBy('month')
                        ->orderBy('month')
                        ->get();
            }
        }
    }

    private function getTopSellingProducts($limit = 10)
    {
        $user = request()->user();
        $isSuperAdmin = $user && $user->role === 'super_admin';
        
        $query = Product::withSum('orderItems as total_sold', 'quantity')
            ->withSum('orderItems as total_revenue', DB::raw('quantity * price'));
        
        if (!$isSuperAdmin) {
            $query->where('created_by', $user->id);
        }
        
        return $query->orderBy('total_sold', 'desc')
            ->limit($limit)
            ->get();
    }

    private function getTopCategories($limit = 10)
    {
        return Category::withCount('products')
            ->withSum('products.orderItems as total_revenue', DB::raw('quantity * price'))
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get();
    }

    private function getSalesTrends($period)
    {
        $user = request()->user();
        $isSuperAdmin = $user && $user->role === 'super_admin';

        if ($isSuperAdmin) {
            return Order::selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as revenue')
                ->whereBetween('created_at', [now()->subDays(30), now()])
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } else {
            return OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('products.created_by', $user->id)
                ->whereBetween('orders.created_at', [now()->subDays(30), now()])
                ->selectRaw('DATE(orders.created_at) as date, COUNT(DISTINCT orders.id) as orders, SUM(order_items.quantity * order_items.price) as revenue')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        }
    }

    private function getHourlySales()
    {
        $user = request()->user();
        $isSuperAdmin = $user && $user->role === 'super_admin';

        if ($isSuperAdmin) {
            return Order::selectRaw('HOUR(created_at) as hour, COUNT(*) as orders, SUM(total_amount) as revenue')
                ->whereDate('created_at', today())
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();
        } else {
            return OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('products.created_by', $user->id)
                ->whereDate('orders.created_at', today())
                ->selectRaw('HOUR(orders.created_at) as hour, COUNT(DISTINCT orders.id) as orders, SUM(order_items.quantity * order_items.price) as revenue')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();
        }
    }

    private function getDailySales()
    {
        $user = request()->user();
        $isSuperAdmin = $user && $user->role === 'super_admin';

        if ($isSuperAdmin) {
            return Order::selectRaw('DAYOFWEEK(created_at) as day, COUNT(*) as orders, SUM(total_amount) as revenue')
                ->whereBetween('created_at', [now()->subDays(7), now()])
                ->groupBy('day')
                ->orderBy('day')
                ->get();
        } else {
            return OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('products.created_by', $user->id)
                ->whereBetween('orders.created_at', [now()->subDays(7), now()])
                ->selectRaw('DAYOFWEEK(orders.created_at) as day, COUNT(DISTINCT orders.id) as orders, SUM(order_items.quantity * order_items.price) as revenue')
                ->groupBy('day')
                ->orderBy('day')
                ->get();
        }
    }

    private function calculateRepeatPurchaseRate()
    {
        $totalCustomers = User::customers()->has('orders')->count();
        $repeatCustomers = User::customers()->has('orders', '>', 1)->count();
        
        return $totalCustomers > 0 ? round(($repeatCustomers / $totalCustomers) * 100, 2) : 0;
    }

    private function calculateCustomerRetentionRate()
    {
        // Simplified retention rate calculation
        $customersLastMonth = User::customers()->whereMonth('created_at', now()->subMonth()->month)->count();
        $retainedCustomers = User::customers()
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereHas('orders', function($q) {
                $q->whereMonth('created_at', now()->month);
            })->count();
            
        return $customersLastMonth > 0 ? round(($retainedCustomers / $customersLastMonth) * 100, 2) : 0;
    }

    private function getOnlineUsersCount()
    {
        // This would typically track active sessions or recent activity
        return User::where('last_login_at', '>=', now()->subMinutes(15))->count();
    }

    // Additional helper methods would be implemented here...
    private function getGeographicDistribution() { return []; }
    private function getCustomerAcquisitionData() { return []; }
    private function getTopCustomers() { return []; }
    private function getBestSellingProducts() { return []; }
    private function getWorstPerformingProducts() { return []; }
    private function getMostViewedProducts() { return []; }
    private function getMostWishlistedProducts() { return []; }
    private function getCategoryPerformance() { return []; }
    private function getPriceAnalysis() { return []; }
    private function getStockAlerts() { return []; }
    private function calculatePeriodGrowth($period) { return 0; }
    private function getTopSellingProductInPeriod($from, $to) { return null; }
    private function getReturningCustomersInPeriod($from, $to) { return 0; }
    private function calculateCustomerAcquisitionCost($from, $to) { return 0; }
    private function getDetailedBreakdown($from, $to) { return []; }
}
