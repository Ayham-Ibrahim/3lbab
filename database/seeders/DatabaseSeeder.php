<?php

namespace Database\Seeders;

use App\Models\Color;
use App\Models\User;
use Database\Seeders\RolesPermissions\CustomerSeeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\RolesPermissions\PermissionSeeder;
use Database\Seeders\RolesPermissions\StoreManagerSeeder;
use Database\Seeders\RolesPermissions\SuperAdminSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            SuperAdminSeeder::class,
            StoreManagerSeeder::class,
            CustomerSeeder::class,
            ColorsSeeder::class,
            SizesSeeder::class,
        ]);
    }
}
