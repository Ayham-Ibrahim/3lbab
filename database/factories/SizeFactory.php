<?php

namespace Database\Factories;

use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Size>
 */
class SizeFactory extends Factory
{
    protected $model = Size::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['clothes', 'shoes', 'general'];
        $type = $this->faker->randomElement($types);

        $sizeCode = $this->generateSizeCode($type);

        return [
            'type' => $type,
            'size_code' => $sizeCode,
            'is_available' => $this->faker->boolean(90),
        ];
    }

    private function generateSizeCode(string $type): string
    {
        return match ($type) {
            'clothes' => $this->faker->randomElement(['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL']),
            'shoes' => (string)$this->faker->numberBetween(36, 46),
            default => (string)$this->faker->numberBetween(1, 10),
        };
    }
}
