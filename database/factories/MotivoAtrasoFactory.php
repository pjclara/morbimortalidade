<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MotivoAtraso>
 */
class MotivoAtrasoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->sentence(3),
            'ativo' => true,
        ];
    }
}
