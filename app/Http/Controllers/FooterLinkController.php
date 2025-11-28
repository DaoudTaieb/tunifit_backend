<?php

namespace App\Http\Controllers;

use App\Models\FooterLink;
use Illuminate\Http\Request;

class FooterLinkController extends Controller
{
    /**
     * Get all active footer links
     */
    public function index()
    {
        return FooterLink::where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    /**
     * Get all footer links (admin)
     */
    public function all()
    {
        return FooterLink::orderBy('order')->get();
    }

    /**
     * Store a new footer link
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|string|max:500',
            'order' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $link = FooterLink::create($data);

        return response()->json($link, 201);
    }

    /**
     * Update a footer link
     */
    public function update(Request $request, FooterLink $footerLink)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|string|max:500',
            'order' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $footerLink->update($data);

        return response()->json($footerLink);
    }

    /**
     * Delete a footer link
     */
    public function destroy(FooterLink $footerLink)
    {
        $footerLink->delete();

        return response()->json(['message' => 'Footer link deleted']);
    }
}
