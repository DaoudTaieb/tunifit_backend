<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
   public function index()
{
    $notifications = Notification::orderByDesc('created_at')->get();

    return response()->json($notifications);
}


public function store(Request $request)
{
    $data = $request->validate([
        'created_by' => ['nullable', 'integer'],   // user who triggered it
        'type'       => ['required', 'string', 'max:100'],
        'title'      => ['required', 'string', 'max:255'],
        'message'    => ['nullable', 'string'],
        'data'       => ['nullable', 'array'],
    ]);

    $notification = Notification::create($data);

    return response()->json($notification, 201);
}


    public function markAsRead(Notification $notification)
{
    if (!$notification->read_at) {
        $notification->update(['read_at' => now()]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Notification marked as read successfully.'
    ]);
}


    public function markAllAsRead()
    {
        Notification::whereNull('read_at')->update(['read_at' => now()]);
        return response()->json(['status' => 'ok']);
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();
        return response()->json(['status' => 'deleted']);
    }

    // 🆕 DELETE all notifications (optional)
    public function destroyAll()
    {
        Notification::truncate();
        return response()->json(['status' => 'all deleted']);
    }
}