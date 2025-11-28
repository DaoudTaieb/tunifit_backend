<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    /**
     * Get all active testimonials
     */
    public function index()
    {
        return Testimonial::where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    /**
     * Get all testimonials (admin)
     */
    public function all()
    {
        return Testimonial::orderBy('order')->get();
    }

    /**
     * Store a new testimonial
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'nullable|string|max:255',
            'content' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'image_url' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'order' => 'sometimes|integer',
        ]);

        $testimonial = Testimonial::create($data);

        return response()->json($testimonial, 201);
    }

    /**
     * Update a testimonial
     */
    public function update(Request $request, Testimonial $testimonial)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'role' => 'nullable|string|max:255',
            'content' => 'sometimes|string',
            'rating' => 'sometimes|integer|min:1|max:5',
            'image_url' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'order' => 'sometimes|integer',
        ]);

        $testimonial->update($data);

        return response()->json($testimonial);
    }

    /**
     * Delete a testimonial
     */
    public function destroy(Testimonial $testimonial)
    {
        $testimonial->delete();

        return response()->json(['message' => 'Testimonial deleted']);
    }
}
