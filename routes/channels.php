<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('admin-notifications', function () {
    // This is a public channel - no authentication required
    // In production, you might want to restrict this to admin users only
    return true;
});

Broadcast::channel('customer.notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
