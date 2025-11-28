<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AppSetting;

class CheckAppSettings
{
    public function handle(Request $request, Closure $next)
    {
        $settings = AppSetting::first();

        if ($settings && $settings->maintenance_mode) {
            // Match admin anywhere in the path (works for "admin/..." and "api/admin/...")
            $isAdminRoute = $request->is('*admin/*');

            // Explicitly allow admin auth endpoints (POST)
            $isAdminAuthLogin    = $request->is('*admin/auth/login') && $request->isMethod('post');
            $isAdminAuthRegister = $request->is('*admin/auth/register') && $request->isMethod('post');

            // Allow GET /app-settings (works with or without api prefix)
            $isAppSettingsGet = ($request->is('*app-settings') || $request->is('*app-settings')) && $request->isMethod('get');
            $isAppSettingspost = ($request->is('*app-settings') || $request->is('*app-settings')) && $request->isMethod('post');

            //Wishlist and cart
            $WishlistGet = ($request->is('*wishlist') || $request->is('*wishlist')) && $request->isMethod('get');
            $cartGet = ($request->is('*cart') || $request->is('*cart')) && $request->isMethod('get');

            //GetUser
            $UserGet = ($request->is('*/auth/user') || $request->is('*/auth/user')) && $request->isMethod('get');

            if (! ($isAdminRoute || $isAdminAuthLogin || $isAdminAuthRegister || $isAppSettingsGet || $isAppSettingspost || $WishlistGet || $cartGet || $UserGet) ) {
                return response()->json([
                    'message' => 'The application is under maintenance. Please try again later.'
                ], 503);
            }
        }

        if ($settings && $settings->allow_register) {
            if ($request->is('*auth/register') || $request->is('*auth/register')) {
                return response()->json([
                    'message' => 'Registration is currently disabled.'
                ], 403);
            }
        }

        return $next($request);
    }
}
