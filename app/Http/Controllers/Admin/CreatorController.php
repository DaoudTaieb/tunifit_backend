<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CreatorController extends Controller
{
    /**
     * Get all creators (users who can create products - admins)
     */
    public function index(Request $request)
    {
        $creators = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
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
                    'created_at' => $creator->created_at,
                ];
            });

        return response()->json([
            'creators' => $creators
        ]);
    }

    /**
     * Create a new creator
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'avatar' => 'nullable|string', // URL or base64 image
        ]);

        $creator = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => User::ROLE_ADMIN, // Creators are admins
            'status' => User::STATUS_ACTIVE,
            'avatar' => $request->avatar,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'Creator created successfully',
            'creator' => [
                'id' => $creator->id,
                'first_name' => $creator->first_name,
                'last_name' => $creator->last_name,
                'full_name' => $creator->first_name . ' ' . $creator->last_name,
                'email' => $creator->email,
                'avatar' => $creator->avatar,
            ]
        ], 201);
    }

    /**
     * Update a creator
     */
    public function update(Request $request, $id)
    {
        $creator = User::findOrFail($id);

        $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($id)],
            'password' => 'nullable|string|min:8',
            'avatar' => 'nullable|string',
        ]);

        $data = $request->only(['first_name', 'last_name', 'email', 'avatar']);
        
        // Update password only if provided
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $creator->update($data);

        return response()->json([
            'message' => 'Creator updated successfully',
            'creator' => [
                'id' => $creator->id,
                'first_name' => $creator->first_name,
                'last_name' => $creator->last_name,
                'full_name' => $creator->first_name . ' ' . $creator->last_name,
                'email' => $creator->email,
                'avatar' => $creator->avatar,
            ]
        ]);
    }

    /**
     * Delete a creator
     */
    public function destroy($id)
    {
        $creator = User::findOrFail($id);
        
        // Check if creator has products
        if ($creator->createdProducts()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete creator with existing products',
            ], 400);
        }

        $creator->delete();

        return response()->json([
            'message' => 'Creator deleted successfully'
        ]);
    }
}

