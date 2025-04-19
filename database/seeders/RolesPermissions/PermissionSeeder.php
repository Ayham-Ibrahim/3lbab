<?php

namespace Database\Seeders\RolesPermissions;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define a list of permissions
        $permissions = [
            'list-colors', 'store-colors', 'update-colors', 'show-colors', 'delete-colors', 'toggle-available-colors',
            'list-sizes', 'store-sizes', 'update-sizes', 'show-sizes', 'delete-sizes', 'toggle-available-sizes',
            'list-stores', 'store-stores', 'update-stores', 'update-myStore', 'show-stores', 'show-myStore', 'delete-stores', 'toggle-available-stores',
            'list-categories', 'store-categories', 'update-categories', 'show-categories', 'delete-categories', 'toggle-available-categories',
            'list-products', 'list-my-products', 'store-my-products', 'store-products', 'update-products', 'show-products', 'delete-products', 'delete-product-images', 'delete-product-variants'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'api']
            );
        }
    }
}
