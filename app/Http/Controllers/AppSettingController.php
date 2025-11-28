<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;

class AppSettingController extends Controller
{
    // Show current settings
    public function index()
    {
        $settings = AppSetting::first();
        return response()->json($settings);
    }

    // Update settings
    public function update(Request $request)
    {
        $request->validate([
            'allow_register' => 'boolean',
            'maintenance_mode' => 'boolean',
        ]);

        $settings = AppSetting::first();

        if (!$settings) {
            $settings = new AppSetting();
        }

        $settings->fill($request->only(['allow_register', 'maintenance_mode']));
        $settings->save();

        return response()->json([
            'message' => 'Settings updated successfully',
            'data' => $settings
        ]);
    }
}
