<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only insert if not already present (safe to re-run)
        if (DB::table('users')->where('id', config('tavan.system_user_id'))->exists()) {
            return;
        }

        DB::table('users')->insert([
            'id'                 => config('tavan.system_user_id'),
            'name'               => 'Tavan Podrška',
            'username'           => 'tavan.podrska',
            'email'              => 'support@tavan.store',
            'password'           => bcrypt(\Illuminate\Support\Str::random(64)), // not usable for login
            'role'               => 'user',
            'is_system'          => true,
            'is_verified'        => true,
            'profile_setup_done' => true,
            'feed_setup_done'    => true,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('id', config('tavan.system_user_id'))->delete();
    }
};
