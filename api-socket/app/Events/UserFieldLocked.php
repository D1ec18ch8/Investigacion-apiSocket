<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
/**
 * Broadcasts when a user locks a specific field so others can block editing.
 */
class UserFieldLocked implements ShouldBroadcast
{
    // 1. Necessary Traits for Event and Broadcasting functionality.
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Constructor for the event.
     * Defines the public properties that will be sent as the payload to clients.
     */
    public function __construct(
        // ID of the user whose field is being locked.
        public int $userId,

        // Name of the field that is locked.
        public string $field,

        // ID of the user who acquired the lock (the current editor).
        public int $byId,

        // Name of the user who acquired the lock.
        public string $byName
    ) {
        // Constructor property promotion automatically assigns the values.
    }

    /**
     * Defines the channels to which the event will be broadcast.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcasts to a public channel named 'user-management'.
        // All clients subscribed to this channel will receive the event notification.
        return [new Channel('user-management')];
    }
}
