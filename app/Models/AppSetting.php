<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = [
        'allow_register',
        'maintenance_mode',
    ];

    protected $casts = [
        'allow_register' => 'boolean',
        'maintenance_mode' => 'boolean',
    ];
}
