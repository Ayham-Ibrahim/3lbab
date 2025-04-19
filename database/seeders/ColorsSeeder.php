<?php

namespace Database\Seeders;

use App\Models\Color;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ColorsSeeder extends Seeder
{
    private array $mainColors = [
        [
            'name' => 'أحمر',
            'hex_code' => '#FF0000',
            'is_available' => true,
        ],
        [
            'name' => 'أخضر',
            'hex_code' => '#00FF00',
            'is_available' => true,
        ],
        [
            'name' => 'أزرق',
            'hex_code' => '#0000FF',
            'is_available' => true,
        ],
        [
            'name' => 'أصفر',
            'hex_code' => '#FFFF00',
            'is_available' => true,
        ],
        [
            'name' => 'أرجواني',
            'hex_code' => '#800080',
            'is_available' => true,
        ],
        [
            'name' => 'برتقالي',
            'hex_code' => '#FFA500',
            'is_available' => true,
        ],
        [
            'name' => 'زهري',
            'hex_code' => '#FFC0CB',
            'is_available' => true,
        ],
        [
            'name' => 'بني',
            'hex_code' => '#A52A2A',
            'is_available' => true,
        ],
        [
            'name' => 'أسود',
            'hex_code' => '#000000',
            'is_available' => true,
        ],
        [
            'name' => 'أبيض',
            'hex_code' => '#FFFFFF',
            'is_available' => true,
        ],
        [
            'name' => 'رمادي',
            'hex_code' => '#808080',
            'is_available' => true,
        ],
        [
            'name' => 'فيروزي',
            'hex_code' => '#40E0D0',
            'is_available' => true,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mainColorNames = array_column($this->mainColors, 'name');

        foreach ($this->mainColors as $color) {
            Color::firstOrCreate(
                ['name' => $color['name']],
                $color
            );
        }
        $existingNames = Color::pluck('name')->toArray();

        Color::factory()
            ->count(10)
            ->make()
            ->each(function ($color) use (&$existingNames) {
                while (in_array($color->name, $existingNames)) {
                    $color->name = $color->name . '_' . rand(1, 1000);
                }
                $existingNames[] = $color->name;
                $color->save();
            });
    }
}
