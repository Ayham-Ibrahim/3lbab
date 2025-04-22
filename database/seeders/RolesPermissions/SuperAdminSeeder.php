<?php

namespace Database\Seeders\RolesPermissions;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'api']
        );

        $permissions = Permission::where('guard_name', 'api')->pluck('id');
        $superAdminRole->syncPermissions($permissions);

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password')
            ]
        );

        $superAdmin->assignRole('admin');
    }
}
