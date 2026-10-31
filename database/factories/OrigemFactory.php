<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Origem>
 */
class OrigemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => fake()->randomElement(['Urgência', 'Consulta', 'Programado']),
        ];
    }
}
