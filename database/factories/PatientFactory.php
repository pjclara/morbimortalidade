<?php

namespace Database\Factories;

use App\Models\Sexo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'processo' => fake()->unique()->numerify('########'),
            'data_nascimento' => fake()->date(),
            'sexo_id' => Sexo::factory(),
        ];
    }
}
