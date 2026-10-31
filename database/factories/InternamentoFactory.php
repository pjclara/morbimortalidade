<?php

namespace Database\Factories;

use App\Models\Destino;
use App\Models\Origem;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Internamento>
 */
class InternamentoFactory extends Factory
{
    public function definition(): array
    {
        $dataEntrada = fake()->dateTimeBetween('-6 months', '-1 month');
        $dataSaida = (clone $dataEntrada)->modify('+'.fake()->numberBetween(1, 20).' days');

        return [
            'patient_id' => Patient::factory(),
            'episodio' => fake()->unique()->numerify('EP######'),
            'data_entrada' => $dataEntrada->format('Y-m-d'),
            'data_alta' => null,
            'data_saida' => $dataSaida->format('Y-m-d'),
            'dias_internamento' => $dataEntrada->diff($dataSaida)->days,
            // 'Domicílio' por omissão — evita que testes que não mexem no
            // destino caiam aleatoriamente em "Transf Interna" (que exige
            // `servico_destino`) por causa do randomElement do DestinoFactory.
            'destino_id' => Destino::factory()->state(['nome' => 'Domicílio']),
            'origem_id' => Origem::factory(),
            'falecido' => false,
            'falecido_apos_alta' => false,
        ];
    }
}
