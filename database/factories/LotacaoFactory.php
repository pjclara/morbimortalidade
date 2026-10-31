<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lotacao>
 */
class LotacaoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'data_inicio' => fake()->date(),
            'data_fim' => null,
            'numero_camas' => fake()->numberBetween(40, 80),
            'observacoes' => null,
        ];
    }
}
