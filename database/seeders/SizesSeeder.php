<?php

namespace Database\Seeders;

use App\Models\Size;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SizesSeeder extends Seeder
{
    private array $standardSizes = [
        [
            'type' => 'clothes',
            'size_code' => 'XS',
            'is_available' => true,
        ],
        [
            'type' => 'clothes',
            'size_code' => 'S',
            'is_available' => true,
        ],
        [
            'type' => 'clothes',
            'size_code' => 'M',
            'is_available' => true,
        ],
        [
            'type' => 'clothes',
            'size_code' => 'L',
            'is_available' => true,
        ],
        [
            'type' => 'clothes',
            'size_code' => 'XL',
            'is_available' => true,
        ],
        [
            'type' => 'clothes',
            'size_code' => 'XXL',
            'is_available' => true,
        ],

        [
            'type' => 'shoes',
            'size_code' => '36',
            'is_available' => true,
        ],
        [
            'type' => 'shoes',
            'size_code' => '38',
            'is_available' => true,
        ],
        [
            'type' => 'shoes',
            'size_code' => '40',
            'is_available' => true,
        ],
        [
            'type' => 'shoes',
            'size_code' => '42',
            'is_available' => true,
        ],
        [
            'type' => 'shoes',
            'size_code' => '44',
            'is_available' => true,
        ],

        [
            'type' => 'general',
            'size_code' => 'ONE SIZE',
            'is_available' => true,
        ],
        [
            'type' => 'general',
            'size_code' => 'SMALL',
            'is_available' => true,
        ],
        [
            'type' => 'general',
            'size_code' => 'MEDIUM',
            'is_available' => true,
        ],
        [
            'type' => 'general',
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
                    'type' => $size['type'],
                    'size_code' => $size['size_code']
                ],
                $size
            );
        }

        $existingCombinations = Size::select('type', 'size_code')
            ->get()
            ->map(fn($item) => $item->type . '-' . $item->size_code)
            ->toArray();

        Size::factory()
            ->count(20)
            ->make()
            ->each(function ($size) use (&$existingCombinations) {
                $combination = $size->type . '-' . $size->size_code;

                while (in_array($combination, $existingCombinations)) {
                    if ($size->type === 'clothes') {
                        $size->size_code = $size->size_code . '_' . rand(1, 100);
                    } else {
                        $size->size_code = (int)$size->size_code + rand(1, 5);
                    }
                    $combination = $size->type . '-' . $size->size_code;
                }

                $existingCombinations[] = $combination;
                $size->save();
            });
    }
}
