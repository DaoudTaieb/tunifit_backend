<?php

namespace App\Events;

use App\Models\CustomerNotification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // or ShouldBroadcastNow
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerNotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public CustomerNotification $notification) {}

    public function broadcastOn(): Channel|array
    {
        // Broadcast to everyone if recipient_user_id is NULL,
        // else to a private channel for that specific user.
        if (is_null($this->notification->recipient_user_id)) {
            return new Channel('customer.notifications'); // public
        }

        return new PrivateChannel('customer.notifications.' . $this->notification->recipient_user_id);
    }

    public function broadcastAs(): string
    {
        return 'CustomerNotificationCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'title' => $this->notification->title,
            'message' => $this->notification->message,
            'type' => $this->notification->type,
            'meta' => $this->notification->meta,
            'read_at' => optional($this->notification->read_at)?->toISOString(),
            'is_active' => $this->notification->is_active,
            'recipient_user_id' => $this->notification->recipient_user_id,
            'created_at' => $this->notification->created_at->toISOString(),
        ];
    }

    // Don’t send inactive/scheduled-for-future notifications
    public function broadcastWhen(): bool
    {
        return $this->notification->is_active &&
               (is_null($this->notification->scheduled_at) || $this->notification->scheduled_at->isPast());
    }
}
