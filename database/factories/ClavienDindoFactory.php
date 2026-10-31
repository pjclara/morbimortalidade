<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClavienDindo>
 */
class ClavienDindoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->word(),
            'descricao' => fake()->sentence(),
        ];
    }
}
