<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcasts when a field lock is released so others can resume editing.
 */
class UserFieldUnlocked implements ShouldBroadcast
{
    // Traits for Event and Broadcasting functionality.
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Constructor for the event.
     * Defines the public properties sent as payload to clients.
     */
    public function __construct(
        // ID of the user whose field lock is being released.
        public int $userId,

        // Name of the field being unlocked (e.g. 'name', 'email').
        public string $field,

        // ID of the user who released the lock.
        public int $byId,

        // Name of the user who released the lock (for UI feedback).
        public string $byName
    ) {
    }

    /**
     * Broadcast on the public 'user-management' channel.
     * All authenticated users listening to this channel receive the event.
     */
    public function broadcastOn(): array
    {
        return [new Channel('user-management')];
    }
}
