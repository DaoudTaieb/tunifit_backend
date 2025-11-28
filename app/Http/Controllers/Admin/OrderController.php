<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 15);
        $search = $request->input('search');
        $status = $request->input('status');
        $paymentStatus = $request->input('payment_status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $user = $request->user();

        $query = Order::with(['user', 'items.product'])
            ->withCount('items');

        // Filter orders by creator's products if user is not super_admin
        if ($user && $user->role !== 'super_admin') {
            $query->whereHas('items.product', function($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('tracking_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($request->has('high_value')) {
            $threshold = $request->input('high_value_threshold', 500);
            $query->where('total_amount', '>=', $threshold);
        }

        if ($request->has('recent')) {
            $days = $request->input('recent_days', 7);
            $query->recentOrders($days);
        }

        $query->orderBy($sortBy, $sortOrder);
        
        $orders = $query->paginate($limit);
        
        return response()->json([
            'orders' => $orders->items(),
            'totalPages' => $orders->lastPage(),
            'currentPage' => $orders->currentPage(),
            'total' => $orders->total(),
            'perPage' => $orders->perPage(),
        ]);
    }

   public function show($id)
{
    $user = request()->user();
    $query = Order::query()
        ->with(['user', 'items.product']) // safe relations only
        ->withCount('items');

    // Filter orders by creator's products if user is not super_admin
    if ($user && $user->role !== 'super_admin') {
        $query->whereHas('items.product', function($q) use ($user) {
            $q->where('created_by', $user->id);
        });
    }

    $order = $query->findOrFail($id);

    // If your Order model has $appends that compute status timeline, disable for this response:
    $order->setAppends([]);               // remove all appended attributes
    // or: $order->makeHidden(['status_timeline', 'status_histories']); // hide specific ones

    return response()->json(['order' => $order]);
}


public function updateStatus(Request $request, $id)
{
    // Your table enum: pending, processing, shipped, delivered, cancelled
    $allowedStatuses = ['pending','processing','shipped','delivered','cancelled'];

    $request->validate([
        'status' => ['required', Rule::in($allowedStatuses)],
        // removed: notes, tracking_number (not in schema)
    ]);

    $order = Order::findOrFail($id);
    $oldStatus = $order->status;

    DB::transaction(function () use ($order, $request) {
        // Only 'status' exists in your schema
        $order->update(['status' => $request->status]);

        // removed: OrderStatusHistory write (table not in schema)
        // removed: shipped_at/delivered_at/cancelled_at/refunded_at/tracking_number (columns not in schema)
    });

    return response()->json([
        'message' => 'Order status updated successfully',
        'order'   => $order->fresh(['user', 'items.product']),
    ]);
}


    public function updatePaymentStatus(Request $request, $id)
{
    $allowed = method_exists(Order::class, 'getPaymentStatuses')
        ? Order::getPaymentStatuses()
        : ['pending', 'paid', 'failed', 'refunded'];

    $request->validate([
        'payment_status' => ['required', Rule::in($allowed)],
        'notes' => 'nullable|string|max:500',
    ]);

    $order = Order::findOrFail($id);

    // If the column doesn't exist, bail out with a clear error
    if (!Schema::hasColumn('orders', 'payment_status')) {
        return response()->json([
            'message' => "Cannot update payment status: 'orders.payment_status' column is missing."
        ], 422);
    }

    DB::transaction(function () use ($order, $request) {
        // Update the column (only if present)
        $order->update(['payment_status' => $request->payment_status]);

        // Optional history logging only if the table exists
        if (Schema::hasTable('order_status_histories')) {
            \DB::table('order_status_histories')->insert([
                'order_id'    => $order->id,
                'status'      => 'Payment: ' . $request->payment_status,
                'notes'       => $request->notes,
                'changed_by'  => auth()->id(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    });

    return response()->json([
        'message' => 'Payment status updated successfully',
        'order'   => $order->fresh(),
    ]);
}

    public function addNotes(Request $request, $id)
{
    $request->validate([
        'notes' => 'required|string|max:1000',
    ]);

    $order = Order::findOrFail($id);

    // Build a neat log line to append
    $by   = auth()->check() ? ('user#' . auth()->id()) : 'system';
    $line = '[' . now()->format('Y-m-d H:i:s') . ' ' . $by . '] ' . $request->notes;

    $order->notes = $request->notes;

    $order->save();

    return response()->json([
        'message' => 'Notes added successfully',
        'notes'   => $order->notes,
        'order_id'=> $order->id,
    ]);
}

    public function getStats()
    {
        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::pending()->count(),
            'processing_orders' => Order::where('status', Order::STATUS_PROCESSING)->count(),
            'shipped_orders' => Order::shipped()->count(),
            'delivered_orders' => Order::delivered()->count(),
            'cancelled_orders' => Order::cancelled()->count(),
            'refunded_orders' => Order::refunded()->count(),
            'total_revenue' => Order::delivered()->sum('total_amount'),
            'pending_revenue' => Order::whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PROCESSING])->sum('total_amount'),
            'average_order_value' => Order::delivered()->avg('total_amount'),
            'orders_today' => Order::whereDate('created_at', today())->count(),
            'orders_this_week' => Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'orders_this_month' => Order::whereMonth('created_at', now()->month)->count(),
        ];

        return response()->json(['stats' => $stats]);
    }

    public function getRevenueChart(Request $request)
    {
        $period = $request->input('period', 'week'); // week, month, year
        
        $query = Order::delivered();
        
        switch ($period) {
            case 'week':
                $data = $query->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
                    ->whereBetween('created_at', [now()->subDays(7), now()])
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
                break;
            case 'month':
                $data = $query->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
                    ->whereBetween('created_at', [now()->subDays(30), now()])
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
                break;
            case 'year':
                $data = $query->selectRaw('MONTH(created_at) as month, SUM(total_amount) as revenue')
                    ->whereYear('created_at', now()->year)
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get();
                break;
        }

        return response()->json(['chart_data' => $data]);
    }

    public function export(Request $request)
{
    $allowedStatuses = method_exists(Order::class, 'getStatuses')
        ? Order::getStatuses()
        : ['pending','processing','shipped','delivered','cancelled'];

    $request->validate([
        'format'    => ['required', Rule::in(['csv','excel'])],
        'date_from' => 'nullable|date',
        'date_to'   => 'nullable|date',
        'status'    => ['nullable', Rule::in($allowedStatuses)],
    ]);

    $query = Order::with(['user','items.product']);

    if ($request->date_from) {
        $query->whereDate('created_at', '>=', $request->date_from);
    }
    if ($request->date_to) {
        $query->whereDate('created_at', '<=', $request->date_to);
    }
    if ($request->status) {
        $query->where('status', $request->status);
    }

    $orders = $query->get();

    return response()->json([
        'message'      => 'Export prepared',
        'orders_count' => $orders->count(),
        'export_data'  => $orders->map(function ($order) {
            $first = $order->user->first_name ?? '';
            $last  = $order->user->last_name ?? '';
            return [
                'order_number' => $order->order_number,
                'customer'     => trim($first.' '.$last), // avoids requiring a full_name accessor
                'email'        => $order->user->email ?? null,
                'status'       => $order->status,
                'total_amount' => $order->total_amount,
                'created_at'   => $order->created_at->format('Y-m-d H:i:s'),
            ];
        }),
    ]);
}

   
}
