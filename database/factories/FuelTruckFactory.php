<?php

namespace Database\Factories;

use App\Models\FuelTruck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FuelTruck>
 */
class FuelTruckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'truck_code' => fake()->unique()->bothify('MT-###'),
            'plate_number' => strtoupper(fake()->unique()->bothify('B #### ??')),
            'capacity_liter' => 8000,
            'driver_name' => fake()->name(),
            'status' => 'ACTIVE',
        ];
    }
}
