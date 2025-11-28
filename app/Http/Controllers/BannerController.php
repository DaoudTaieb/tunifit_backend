<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index()
    {
        return Banner::orderBy('order')->get();
    }

    public function store(Request $request)
{
    // Validate inputs
    $data = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        'button_text' => 'nullable|string|max:50',
        'button_link' => 'nullable|url',
    ]);

    // Upload the image
    if ($request->hasFile('image')) {
        $data['image_url'] = $request->file('image')->store('banners', 'public');
        // $data['image_url'] will be something like "banners/myimage.jpg"
    }

    // Set default values
    if (!isset($data['is_active'])) {
        $data['is_active'] = true;
    }
    if (!isset($data['order'])) {
        $data['order'] = 0;
    }

    // Create the banner in the database
    $banner = Banner::create($data);

    // Return the banner
    return response()->json($banner, 201);
}


    public function update(Request $request, Banner $banner)
{
    $data = $request->validate([
        'title' => 'sometimes|string|max:255',
        'description' => 'sometimes|string',
        'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:5120',
        'button_text' => 'sometimes|string|max:50',
        'button_link' => 'sometimes|url',
        'order' => 'sometimes|integer',
        'is_active' => 'sometimes|boolean',
    ]);

    // Handle image upload
    if ($request->hasFile('image')) {
        $data['image_url'] = $request->file('image')->store('banners', 'public');
    }

    $banner->update($data);

    return $banner;
}

    public function destroy(Banner $banner)
    {
        $banner->delete();
        return response()->json(['message' => 'Banner deleted']);
    }



    public function active()
{
    return Banner::where('is_active', true)->get();
}
}
