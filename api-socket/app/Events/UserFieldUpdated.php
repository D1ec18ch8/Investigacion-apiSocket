<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

/**
 * Broadcasts updated user data so active editors see fresh values instantly.
 */
class UserFieldUpdated implements ShouldBroadcast
{
    // Traits for Event and Broadcasting functionality.
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Constructor for the event.
     * Defines the public properties sent as payload to clients.
     */
    public function __construct(
        // ID of the user whose data was updated.
        public int $userId,

        // Updated name value.
        public string $name,

        // Updated email value.
        public string $email,

        // ID of the user who made the update.
        public int $byId,

        // Name of the user who made the update (for UI feedback).
        public string $byName
    ) {
    }

    /**
     * Factory method to construct event from a User model instance.
     * Simplifies event creation in the controller.
     */
    public static function fromUser(User $user, int $byId, string $byName): self
    {
        return new self($user->id, $user->name, $user->email, $byId, $byName);
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
