<?php

namespace Database\Seeders\RolesPermissions;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class StoreManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $storeManager = Role::firstOrCreate(
            ['name' => 'storeManager', 'guard_name' => 'api']
        );

        $permissions = [
            'store-colors',
            'store-sizes',
            'store-stores',
            'update-myStore',
            'show-myStore',
            'list-categories',
            'store-categories',
            'list-my-products',
            'store-my-products',
            'update-products',
            'show-products',
            'delete-products',
            'delete-product-images',
            'delete-product-variants'
        ];

        $storeManager->syncPermissions($permissions);

        $storeManager1 = User::firstOrCreate(
            ['email' => 'storeManager1@example.com'],
            [
                'name' => 'storeManager1',
                'password' => bcrypt('password')
            ]
        );

        $storeManager2 = User::firstOrCreate(
            ['email' => 'storeManager2@example.com'],
            [
                'name' => 'storeManager2',
                'password' => bcrypt('password')
            ]
        );

        $storeManager1->assignRole('storeManager');
        $storeManager2->assignRole('storeManager');
    }
}
