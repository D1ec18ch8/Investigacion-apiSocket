<?php

use App\Events\SocketEvent;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/socktest', function () {
    event(new SocketEvent('Hello World'));
    return 'Event has been sent!';
});
