<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BillFactory extends Factory
{
    protected $model = Bill::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['iuran', 'sumbangan', 'lainnya']),
            'title' => 'Iuran ' . $this->faker->monthName(),
            'description' => $this->faker->sentence(),
            'amount' => $this->faker->numberBetween(10000, 150000),
            'gateway_fee' => 0,
            'total_amount' => null,
            'due_date' => now()->addDays($this->faker->numberBetween(1, 20)),
            'status' => 'pending',
            'invoice_number' => strtoupper($this->faker->bothify('INV-####')),
            'issued_at' => now(),
            'created_by' => null,
        ];
    }
}
