<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipient_user_id',
        'title',
        'message',
        'type',
        'meta',
        'read_at',
        'is_active',
        'scheduled_at',
    ];

    protected $casts = [
        'meta'         => 'array',
        'read_at'      => 'datetime',
        'is_active'    => 'boolean',
        'scheduled_at' => 'datetime',
    ];

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    /**
     * Scope: notifications visible to a given user.
     * Includes broadcasts (recipient_user_id NULL) and personal ones.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereNull('recipient_user_id')
              ->orWhere('recipient_user_id', $userId);
        });
    }
}
