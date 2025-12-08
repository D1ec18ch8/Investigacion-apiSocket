<?php

namespace App\Http\Controllers;

use App\Events\UserFieldLocked;
use App\Events\UserFieldUnlocked;
use App\Events\UserFieldUpdated;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for managing user data and collaborative editing.
 * Implements a Redis-based locking mechanism to prevent race conditions
 * when multiple users edit the same user field.
 */
class UserManageController extends Controller
{
    /**
     * Require authentication for all user-management actions.
     */
    public function __construct()
    {
        // Applies the 'auth' middleware to all methods in this controller.
        $this->middleware('auth');
    }

    /**
     * Show paginated users for collaborative editing.
     */
    public function index(): View
    {
        // Fetches 15 users per page, ordered by ID.
        $users = User::orderBy('id')->paginate(15);

        // Returns the 'users.manage' view, passing the paginated user collection.
        return view('users.manage', [
            'users' => $users,
        ]);
    }

    /**
     * Acquire a Redis-backed lock for a specific user field.
     * Rejects with 409 if another user already holds the lock.
     */
    public function lock(Request $request): JsonResponse
    {
        // Validate the required input data.
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            // The field must be either 'name' or 'email'
            'field' => ['required', 'string', 'in:name,email'],
        ]);

        // Build the unique Redis key
        $key = $this->lockKey($validated['user_id'], $validated['field']);
        // Use the 'redis' cache store
        $store = Cache::store('redis');
        // Get the current lock status
        $current = $store->get($key);

        // Conflict Check: If locked by a DIFFERENT user, reject.
        if ($current && $current['byId'] !== $request->user()->id) {
            return response()->json([
                'locked' => true,
                'byId' => $current['byId'],
                'byName' => $current['byName'],
            ], 409); // HTTP 409 Conflict status code
        }

        // Acquisition: Prepare the lock payload (current user's details).
        $payload = [
            'byId' => $request->user()->id, // ID of the user acquiring the lock
            'byName' => $request->user()->name, // Name of the user acquiring the lock
            'lockedAt' => Carbon::now()->toIso8601String(), // Timestamp of the lock
        ];

        // Store the lock in Redis with a TTL (Time To Live) of 120 seconds (2 minutes).
        // The TTL ensures the lock automatically expires if the user fails to unlock it.
        $store->put($key, $payload, 120);

        // Broadcast the UserFieldLocked event for real-time client updates.
        broadcast(new UserFieldLocked(
            userId: $validated['user_id'],
            field: $validated['field'],
            byId: $request->user()->id,
            byName: $request->user()->name,
        ));

        // Return success response.
        return response()->json(['ok' => true]);
    }

    /**
     * Release a Redis-backed lock for a specific user field.
     * Only the user who locked it can unlock it; otherwise, 409 is returned.
     */
    public function unlock(Request $request): JsonResponse
    {
        // Validate the input data.
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'field' => ['required', 'string', 'in:name,email'],
        ]);

        $key = $this->lockKey($validated['user_id'], $validated['field']);
        $store = Cache::store('redis');
        $current = $store->get($key);

        // Authorization Check: Only the locker can unlock.
        if ($current && $current['byId'] !== $request->user()->id) {
            // Return 409 if another user holds the lock.
            return response()->json(['ok' => false, 'lockedByOther' => true], 409);
        }

        // Atomic Delete: Remove the key from Redis to free the lock.
        $store->forget($key);

        // Broadcast the UserFieldUnlocked event for real-time client updates.
        broadcast(new UserFieldUnlocked(
            userId: $validated['user_id'],
            field: $validated['field'],
            byId: $request->user()->id,
            byName: $request->user()->name,
        ));

        // Return success response.
        return response()->json(['ok' => true]);
    }

    /**
     * Persist user changes and broadcast fresh values to listeners.
     * This action is typically called after the user has finished editing a locked field.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        // Validate input data.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // The unique rule ensures the new email is not already taken by another user.
            // By appending $user->id, it ignores the current user's email during the check.
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        // Update the User model in the database.
        $user->update($validated);

        // Broadcast the UserFieldUpdated event with the user's fresh data.
        // This updates the view for all other connected users in real time.
        broadcast(UserFieldUpdated::fromUser(
            $user,
            $request->user()->id,
            $request->user()->name,
        ));

        // Return a redirect back to the previous page with a status message.
        return back()->with('status', 'Usuario actualizado correctamente.');
    }

    /**
     * Centralized Redis key builder for locks.
     *
     * @param int $userId The ID of the user.
     * @param string $field The field being locked.
     * @return string The complete Redis key.
     */
    private function lockKey(int $userId, string $field): string
    {
        return "locks:user:{$userId}:{$field}";
    }
}
