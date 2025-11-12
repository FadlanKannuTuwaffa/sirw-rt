<?php

namespace Database\Factories;

use App\Models\RTOfficial;
use Illuminate\Database\Eloquent\Factories\Factory;

class RTOfficialFactory extends Factory
{
    protected $model = RTOfficial::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'position' => fake()->randomElement(['ketua', 'sekretaris', 'bendahara']),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'address' => fake()->address(),
            'is_active' => true,
            'order' => fake()->numberBetween(1, 10),
        ];
    }
}
