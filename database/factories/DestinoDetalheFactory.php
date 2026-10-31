<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DestinoDetalhe>
 */
class DestinoDetalheFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->sentence(2),
            'destino_id' => null,
        ];
    }
}
