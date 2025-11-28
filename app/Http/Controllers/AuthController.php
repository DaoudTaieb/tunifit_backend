<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Notification;
use App\Events\UserRegistrationNotification;
use App\Events\OrderPlacedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{

    public function register(Request $request)
{
    // Validate all fields including new ones
    $request->validate(
        [
            'firstName'  => 'required|string|max:255',
            'lastName'   => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:users',
            'password'   => 'required|string|min:8',
            'phone'      => 'nullable|string|max:20',
            'address'    => 'nullable|string|max:255',
            'city'       => 'nullable|string|max:100',
            'postalCode' => 'nullable|string|max:20',
            'country'    => 'nullable|string|max:100',
        ],
        [
            'firstName.required' => 'The first name is required.',
            'firstName.string'   => 'The first name must be a valid string.',
            'firstName.max'      => 'The first name may not be greater than 255 characters.',

            'lastName.required'  => 'The last name is required.',
            'lastName.string'    => 'The last name must be a valid string.',
            'lastName.max'       => 'The last name may not be greater than 255 characters.',

            'email.required'     => 'The email field is required.',
            'email.string'       => 'The email must be a valid string.',
            'email.email'        => 'The email must be a valid email address.',
            'email.max'          => 'The email may not be greater than 255 characters.',
            'email.unique'       => 'This email is already registered.',

            'password.required'  => 'The password field is required.',
            'password.string'    => 'The password must be a valid string.',
            'password.min'       => 'The password must be at least 8 characters long.',
        ]
    );

    // Create the user with new fields
    $user = User::create([
        'first_name'  => $request->firstName,
        'last_name'   => $request->lastName,
        'email'       => $request->email,
        'password'    => Hash::make($request->password),
        'phone'       => $request->phone,
        'address'     => $request->address,
        'city'        => $request->city,
        'postal_code' => $request->postalCode,
        'country'     => $request->country,
    ]);

    // Create a notification record
    try {
        $notification = Notification::create([
            'created_by' => null,
            'type'       => 'user.registered',
            'title'      => 'New User Registered',
            'message'    => $user->first_name . ' ' . $user->last_name . ' has registered with email ' . $user->email,
            'data'       => [
                'user_id'     => $user->id,
                'email'       => $user->email,
                'first_name'  => $user->first_name,
                'last_name'   => $user->last_name,
                'phone'       => $user->phone,
                'address'     => $user->address,
                'city'        => $user->city,
                'postal_code' => $user->postal_code,
                'country'     => $user->country,
            ],
        ]);

        // Try to broadcast the notification, but don't fail if Pusher is not available
        try {
            event(new UserRegistrationNotification($notification));
        } catch (\Exception $e) {
            // Log the error but don't fail the registration
            \Log::warning('Failed to broadcast user registration notification: ' . $e->getMessage());
        }
    } catch (\Exception $e) {
        // Log the error but don't fail the registration if notification creation fails
        \Log::warning('Failed to create user registration notification: ' . $e->getMessage());
    }

    return response()->json(['message' => 'User registered successfully'], 201);
}


    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

         if (!Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
        return response()->json([
            'message' => 'Invalid credentials',
        ], 401);
    }

          $user = Auth::user();

        if ($user->role !== 'user') {
        Auth::logout();
        return response()->json([
            'message' => 'Access denied for Admins. Please use the admin login.',
        ], 403);
    }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'address' => $user->address,
                'city' => $user->city,
                'postalCode' => $user->postal_code,
                'country' => $user->country,
            ]
        ]);
    }

    public function adminLogin(Request $request)
    {
         $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

         if (!Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
        return response()->json([
            'message' => 'Invalid credentials',
        ], 401);
    }

        $user = Auth::user();
        
        // Check if user has admin or super_admin role
        if (!in_array($user->role, ['admin', 'super_admin'])) {
        Auth::logout();
        return response()->json([
            'message' => 'Access denied. Admin privileges required.',
        ], 403);
    }

        $token = $user->createToken('admin_auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'admin' => [
                'id' => $user->id,
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'message' => 'Admin logged in successfully'
        ]);
    }

    public function user(Request $request)
    {
        return response()->json([
            'id' => $request->user()->id,
            'firstName' => $request->user()->first_name,
            'lastName' => $request->user()->last_name,
            'email' => $request->user()->email,
            'phone' => $request->user()->phone,
            'city' => $request->user()->city ?? null,
            'address' => $request->user()->address ?? null,
            'postal_code' => $request->user()->postal_code ?? null,
            'country' => $request->user()->country ?? null,
            'role' => $request->user()->role,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function adminRegister(Request $request)
    {
        $request->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $admin = User::create([
            'first_name' => $request->firstName,
            'last_name' => $request->lastName,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
        ]);

        return response()->json([
            'message' => 'Admin registered successfully',
            'admin' => [
                'id' => $admin->id,
                'firstName' => $admin->first_name,
                'lastName' => $admin->last_name,
                'email' => $admin->email,
                'role' => $admin->role,
            ]
        ], 201);
    }

    public function adminProfile(Request $request)
    {
        $admin = $request->user();
        
        if (!in_array($admin->role, ['admin', 'super_admin'])) {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
        }

        return response()->json([
            'admin' => [
                'id' => $admin->id,
                'firstName' => $admin->first_name,
                'lastName' => $admin->last_name,
                'email' => $admin->email,
                'role' => $admin->role,
                'created_at' => $admin->created_at,
                'updated_at' => $admin->updated_at,
            ]
        ]);
    }

    public function updateAdminProfile(Request $request)
    {
        $admin = $request->user();
        
        if (!in_array($admin->role, ['admin', 'super_admin'])) {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
        }

        $request->validate([
            'firstName' => 'sometimes|required|string|max:255',
            'lastName' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $admin->id,
            'current_password' => 'required_with:password|string',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        // Verify current password if trying to change password
        if ($request->has('password')) {
            if (!Hash::check($request->current_password, $admin->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['The current password is incorrect.'],
                ]);
            }
        }

        $updateData = [];
        if ($request->has('firstName')) {
            $updateData['first_name'] = $request->firstName;
        }
        if ($request->has('lastName')) {
            $updateData['last_name'] = $request->lastName;
        }
        if ($request->has('email')) {
            $updateData['email'] = $request->email;
        }
        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $admin->update($updateData);

        return response()->json([
            'message' => 'Admin profile updated successfully',
            'admin' => [
                'id' => $admin->id,
                'firstName' => $admin->first_name,
                'lastName' => $admin->last_name,
                'email' => $admin->email,
                'role' => $admin->role,
            ]
        ]);
    }
}
