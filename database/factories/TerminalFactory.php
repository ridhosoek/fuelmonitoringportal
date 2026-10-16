<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\Terminal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Terminal>
 */
class TerminalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'terminal_code' => fake()->unique()->bothify('TERM-###'),
            'terminal_name' => fake()->company().' Terminal',
            'description' => fake()->sentence(),
            'status' => 'ACTIVE',
        ];
    }
}
