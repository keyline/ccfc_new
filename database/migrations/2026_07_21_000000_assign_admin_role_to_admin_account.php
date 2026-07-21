<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AssignAdminRoleToAdminAccount extends Migration
{
    public function up()
    {
        $userIds = DB::table('users')
            ->where('email', 'admin@admin.com')
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($userIds->count() !== 1) {
            throw new RuntimeException(
                'Expected exactly one active admin@admin.com account; found ' . $userIds->count() . '.'
            );
        }

        $adminRoleExists = DB::table('roles')
            ->where('id', 1)
            ->where('title', 'Admin')
            ->whereNull('deleted_at')
            ->exists();

        if (! $adminRoleExists) {
            throw new RuntimeException('The active Admin role with ID 1 was not found.');
        }

        $mappingExists = DB::table('role_user')
            ->where('user_id', $userIds->first())
            ->where('role_id', 1)
            ->exists();

        if (! $mappingExists) {
            DB::table('role_user')->insert([
                'user_id' => $userIds->first(),
                'role_id' => 1,
            ]);
        }
    }

    public function down()
    {
        // Preserve the administrator mapping because it may predate this migration.
    }
}
