<?php

namespace App\Services;

use App\Models\BannedDevice;
use App\Models\User;
use Carbon\Carbon;

class BanService
{
    public function ban(User $user, string $duration, ?string $reason = null): void
    {
        $bannedUntil = match ($duration) {
            '7d'        => now()->addDays(7),
            '30d'       => now()->addDays(30),
            'permanent' => Carbon::create(2099, 1, 1),
        };

        $user->update([
            'banned_until' => $bannedUntil,
            'ban_reason'   => $reason,
        ]);

        // Revoke all Sanctum tokens — user is logged out immediately
        $user->tokens()->delete();

        // Hide active/reserved listings — preserved as draft for audit
        $user->products()->whereIn('status', ['active', 'reserved'])->update(['status' => 'draft']);

        // Lock all conversations — history preserved, no new messages
        \App\Models\Conversation::where('participant_one_id', $user->id)
            ->orWhere('participant_two_id', $user->id)
            ->update(['allow_replies' => false]);

        // Fingerprint all known devices into banned_devices
        $deviceIds = $user->pushTokens()
            ->whereNotNull('device_id')
            ->pluck('device_id')
            ->unique();

        foreach ($deviceIds as $deviceId) {
            BannedDevice::firstOrCreate(
                ['device_id' => $deviceId],
                ['banned_at' => now(), 'reason' => $reason],
            );
        }
    }

    public function lift(User $user): void
    {
        $user->update(['banned_until' => null, 'ban_reason' => null]);
    }
}
