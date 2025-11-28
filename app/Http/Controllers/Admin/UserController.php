<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 15);
        $search = $request->input('search');
        $role = $request->input('role');
        $status = $request->input('status');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $query = User::withCount(['orders', 'wishlist'])
            ->withSum('orders as total_spent', 'total_amount');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        if ($role) {
            $query->where('role', $role);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($request->has('verified')) {
            if ($request->verified === 'true') {
                $query->verified();
            } else {
                $query->unverified();
            }
        }

        if ($request->has('has_orders')) {
            $query->has('orders');
        }

        if ($request->has('recent_login')) {
            $days = $request->input('recent_login_days', 30);
            $query->where('last_login_at', '>=', now()->subDays($days));
        }

        $query->orderBy($sortBy, $sortOrder);
        
        $users = $query->paginate($limit);
        
        return response()->json([
            'users' => $users->items(),
            'totalPages' => $users->lastPage(),
            'currentPage' => $users->currentPage(),
            'total' => $users->total(),
            'perPage' => $users->perPage(),
        ]);
    }

    public function show($id)
    {
        $user = User::with(['orders.orderItems.product', 'wishlist'])
            ->withCount(['orders', 'wishlist'])
            ->withSum('orders as total_spent', 'total_amount')
            ->findOrFail($id);

        $analytics = [
            'total_orders' => $user->orders_count,
            'total_spent' => $user->total_spent ?? 0,
            'average_order_value' => $user->orders_count > 0 ? ($user->total_spent ?? 0) / $user->orders_count : 0,
            'wishlist_items' => $user->wishlist_count,
            'last_order_date' => $user->orders()->latest()->first()?->created_at,
            'registration_date' => $user->created_at,
            'last_login' => $user->last_login_at,
            'account_age_days' => $user->created_at->diffInDays(now()),
        ];

        return response()->json([
            'user' => $user,
            'analytics' => $analytics,
        ]);
    }

    public function store(Request $request)
{
    $request->validate([
        'first_name' => 'required|string|max:255',
        'last_name'  => 'required|string|max:255',
        'email'      => 'required|string|email|max:255|unique:users,email',
        'password'   => 'required|string|min:8|confirmed',
        'role'       => 'required|in:user,admin',
    ]);

    $user = User::create([
        'first_name'        => $request->first_name,
        'last_name'         => $request->last_name,
        'email'             => $request->email,
        'password'          => Hash::make($request->password),
        'role'              => $request->role,
        'email_verified_at' => $request->role === 'admin' ? now() : null,
    ]);

    // Make sure we don't leak sensitive fields even if not hidden on the model
    $user->makeHidden(['password', 'remember_token']);

    return response()->json([
        'message' => 'User created successfully',
        'user'    => $user,
    ], 201);
}


    public function update(Request $request, $id)
{
    $user = User::findOrFail($id);

    $request->validate([
        'first_name' => 'sometimes|required|string|max:255',
        'last_name'  => 'sometimes|required|string|max:255',
        'email'      => [
            'sometimes',
            'required',
            'string',
            'email',
            'max:255',
            Rule::unique('users', 'email')->ignore($id),
        ],
        'password'   => 'nullable|string|min:8|confirmed',
        'role'       => 'sometimes|required|in:user,admin',
        
    ]);

    $data = $request->only(['first_name', 'last_name', 'email', 'role']);

    if ($request->filled('password')) {
        $data['password'] = Hash::make($request->password);
    }

    $user->update($data);

    // Don’t leak sensitive fields
    $user->makeHidden(['password', 'remember_token']);

    return response()->json([
        'message' => 'User updated successfully',
        'user'    => $user,
    ]);
}


    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        if ($user->isSuperAdmin()) {
            return response()->json([
                'message' => 'Cannot delete super admin user'
            ], 422);
        }

        if ($user->orders()->exists()) {
            return response()->json([
                'message' => 'Cannot delete user with existing orders. Consider deactivating instead.'
            ], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    

    public function getStats()
    {
        $stats = [
            'total_users' => User::count(),
            'verified_users' => User::verified()->count(),
            'unverified_users' => User::unverified()->count(),
            'admin_users' => User::admins()->count(),
            'customer_users' => User::customers()->count(),
            'users_with_orders' => User::has('orders')->count(),
            'recent_registrations' => User::where('created_at', '>=', now()->subDays(30))->count(),
            
        ];

        return response()->json(['stats' => $stats]);
    }

    public function verifyEmail($id)
{
    $user = User::findOrFail($id);

    
    $alreadyVerified = method_exists($user, 'hasVerifiedEmail')
        ? $user->hasVerifiedEmail()
        : !is_null($user->email_verified_at);

    if ($alreadyVerified) {
        return response()->json(['message' => 'User email is already verified']);
    }

    $user->update(['email_verified_at' => now()]);

    // Don’t leak sensitive fields
    $user->makeHidden(['password', 'remember_token']);

    return response()->json([
        'message' => 'User email verified successfully',
        'user'    => $user,
    ]);
}

    public function resetPassword(Request $request, $id)
{
    $request->validate([
        'password' => 'required|string|min:8|confirmed',
    ]);

    $user = User::findOrFail($id);
    $user->update(['password' => Hash::make($request->password)]);

    return response()->json(['message' => 'Password reset successfully']);
}

    public function impersonate($id)
    {
        $user = User::findOrFail($id);
        
        if ($user->isAdmin()) {
            return response()->json([
                'message' => 'Cannot impersonate admin users'
            ], 422);
        }

        $token = $user->createToken('impersonation_token')->plainTextToken;

        return response()->json([
            'message' => 'Impersonation token generated',
            'token' => $token,
            'user' => $user
        ]);
    }
}
