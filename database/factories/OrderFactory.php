<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = \App\Models\Order::class;

    public function definition(): array
    {
        return [
            'phonenumber' => $this->faker->phoneNumber(),
            'user_id' => User::factory(),
            'address' => $this->faker->address(),
            'order_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'totalprice' => $this->faker->numberBetween(1000, 1000000),
            'status' => $this->faker->randomElement(['pending', 'cancelled', 'delivery', 'success']),
            'payment_date' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'payment_status' => $this->faker->randomElement(['unpaid', 'paid', 'cancelled']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
