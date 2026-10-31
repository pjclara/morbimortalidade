<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Destino>
 */
class DestinoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->randomElement(['Domicílio', 'Falecido', 'Transf Interna', 'Outro Hospital']),
        ];
    }
}
