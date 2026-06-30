<?php

namespace Database\Factories;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Todo>
 */
class TodoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'business_id' => fn (array $attrs) => User::find($attrs['user_id'])?->business_id ?? 1,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'date' => fake()->optional()->date(),
            'status' => fake()->randomElement(['completed', 'undone', 'canceled']),
        ];
    }
}
