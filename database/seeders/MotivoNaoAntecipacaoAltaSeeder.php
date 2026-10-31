<?php

namespace Database\Seeders;

use App\Models\MotivoNaoAntecipacaoAlta;
use Illuminate\Database\Seeder;

class MotivoNaoAntecipacaoAltaSeeder extends Seeder
{
    /**
     * Lista de trabalho — não veio especificada no pedido original, por
     * isso fica como uma primeira proposta a validar com o serviço antes de
     * considerar fechada.
     */
    public function run(): void
    {
        $motivos = [
            'Decisão médica só ficou definida no próprio dia',
            'Aguarda resultado de exame/procedimento',
            'Aguarda parecer de outra especialidade',
            'Indisponibilidade de transporte ou da família',
            'Instabilidade clínica entretanto surgida',
            'Processo administrativo da alta demorado',
            'Fim de semana/feriado sem decisão médica disponível',
            'Outro',
        ];

        foreach ($motivos as $nome) {
            MotivoNaoAntecipacaoAlta::updateOrCreate(['nome' => $nome]);
        }
    }
}
