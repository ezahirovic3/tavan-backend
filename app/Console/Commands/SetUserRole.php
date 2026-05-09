<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetUserRole extends Command
{
    protected $signature = 'user:set-role {email : The user\'s email address} {role : One of: user, admin, super_admin}';

    protected $description = 'Set the role of a user. This is the only way to grant or revoke super_admin.';

    private const VALID_ROLES = ['user', 'admin', 'super_admin'];

    public function handle(): int
    {
        $email = $this->argument('email');
        $role  = $this->argument('role');

        if (! in_array($role, self::VALID_ROLES)) {
            $this->error("Invalid role \"{$role}\". Valid roles are: " . implode(', ', self::VALID_ROLES));
            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email: {$email}");
            return self::FAILURE;
        }

        $previous = $user->role;

        if ($previous === $role) {
            $this->info("User {$email} already has role \"{$role}\". No changes made.");
            return self::SUCCESS;
        }

        // Guard: prevent removing the last super_admin
        if ($previous === 'super_admin' && $role !== 'super_admin') {
            $superAdminCount = User::where('role', 'super_admin')->count();
            if ($superAdminCount <= 1) {
                $this->error('Cannot change role — this is the only super_admin. Assign another super_admin first.');
                return self::FAILURE;
            }
        }

        $user->update(['role' => $role]);

        $this->info("Role updated for {$email}: \"{$previous}\" → \"{$role}\"");
        return self::SUCCESS;
    }
}
