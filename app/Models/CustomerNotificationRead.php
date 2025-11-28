<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerNotificationRead extends Model
{
    protected $fillable = ['notification_id', 'user_id', 'read_at'];
    public $timestamps = false;

    public function notification()
    {
        return $this->belongsTo(CustomerNotification::class, 'notification_id');
    }
}
