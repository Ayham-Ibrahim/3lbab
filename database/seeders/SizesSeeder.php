<?php

namespace Database\Seeders;

use App\Models\Size;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SizesSeeder extends Seeder
{
    private array $standardSizes = [
        [
            'size_code' => 'XS',
            'is_available' => true,
        ],
        [
            'size_code' => 'S',
            'is_available' => true,
        ],
        [
            'size_code' => 'M',
            'is_available' => true,
        ],
        [
            'size_code' => 'L',
            'is_available' => true,
        ],
        [
            'size_code' => 'XL',
            'is_available' => true,
        ],
        [
            'size_code' => 'XXL',
            'is_available' => true,
        ],

        [
            'size_code' => '36',
            'is_available' => true,
        ],
        [
            'size_code' => '38',
            'is_available' => true,
        ],
        [
            'size_code' => '40',
            'is_available' => true,
        ],
        [
            'size_code' => '42',
            'is_available' => true,
        ],
        [
            'size_code' => '44',
            'is_available' => true,
        ],

        [
            'size_code' => 'ONE SIZE',
            'is_available' => true,
        ],
        [
            'size_code' => 'SMALL',
            'is_available' => true,
        ],
        [
            'size_code' => 'MEDIUM',
            'is_available' => true,
        ],
        [
            'size_code' => 'LARGE',
            'is_available' => true,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->standardSizes as $size) {
            Size::firstOrCreate(
                [
                    'size_code' => $size['size_code']
                ],
                $size
            );
        }
    }
}
