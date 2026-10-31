<?php

namespace Database\Factories;

use App\Models\Internamento;
use App\Models\MotivoAtraso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AtrasoAlta>
 */
class AtrasoAltaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'internamento_id' => Internamento::factory(),
            'motivo_id' => MotivoAtraso::factory(),
            'data_sinalizacao_servico_social' => null,
            'data_pedido_rede' => null,
            'observacoes' => null,
        ];
    }
}
