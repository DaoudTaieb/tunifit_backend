<?php

namespace App\Http\Controllers;

use App\Models\CustomerNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CustomerNotificationRead;
use App\Events\CustomerNotificationCreated;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;  

class CustomerNotificationController extends Controller
{
    /**
     * List all notifications for the current user.
     */
    public function index()
    {
        $user = Auth::user();

        $notifications = CustomerNotification::query()    
            ->orderByDesc('created_at')
            ->get();
            

        return response()->json($notifications);    
    }

    /**
     * Create a notification (for user or broadcast).
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $notif = CustomerNotification::create($data);
        event(new CustomerNotificationCreated($notif));
        return response()->json($notif, 201);
    }

    /**
     * Show a single notification if the user has access.
     */
    public function show(CustomerNotification $notification)
    {
        $user = Auth::user();

        if (!is_null($notification->recipient_user_id) &&
            $notification->recipient_user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        return response()->json($notification);
    }

    /**
     * Update a notification.
     */
    public function update(Request $request, CustomerNotification $notification)
    {
        $notification->update($request->all());

        return response()->json($notification);
    }

    /**
     * Delete a notification.
     */
    public function destroy(CustomerNotification $notification)
    {
        $notification->delete();

        return response()->json(['message' => 'Deleted']);
    }

    /**
     * List only active notifications for the current user.
     */
   public function myNotifications()
    {
        $user = Auth::user();

        $notifications = CustomerNotification::query()
            ->where(function ($q) use ($user) {
                $q->whereNull('recipient_user_id')
                  ->orWhere('recipient_user_id', $user->id);
            })
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($notif) use ($user) {
                // ✅ add "is_read" flag for this user
                $notif->is_read = CustomerNotificationRead::where('notification_id', $notif->id)
                    ->where('user_id', $user->id)
                    ->exists();
                return $notif;
            });

        return response()->json($notifications);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(CustomerNotification $notification)
    {
        $user = Auth::user();

        if (!is_null($notification->recipient_user_id) &&
            $notification->recipient_user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        // ✅ store read status per user
        CustomerNotificationRead::updateOrCreate(
            ['notification_id' => $notification->id, 'user_id' => $user->id],
            ['read_at' => now()]
        );

        return response()->json([
            'message' => 'Marked as read',
            'notification' => $notification
        ]);
    }
}
