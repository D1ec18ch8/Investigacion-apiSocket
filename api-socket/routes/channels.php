<?php

use Illuminate\Support\Facades\Broadcast;


Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('channel-private.{userID}', function ($user, $userID) {
    return (int) $user->id === (int) $userID;
});

// Public channel used by collaborative user management to show who edits what.
Broadcast::channel('user-management', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});


