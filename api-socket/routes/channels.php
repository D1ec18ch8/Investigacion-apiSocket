<?php

use Illuminate\Support\Facades\Broadcast;

use function Symfony\Component\String\b;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});


