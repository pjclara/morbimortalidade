<?php

namespace Database\Seeders;

use App\Models\Lotacao;
use Illuminate\Database\Seeder;

class LotacaoSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Períodos de lotação do serviço conforme o relatório
     * "Internamento de Cirurgia: movimento assistencial" (HSA, ULSRL).
     */
    public function run(): void
    {
        $periodos = [
            [
                'data_inicio' => '2025-09-01',
                'data_fim' => '2025-12-31',
                'numero_camas' => 68,
                'observacoes' => 'set.-dez. 2025',
            ],
            [
                'data_inicio' => '2026-01-01',
                'data_fim' => '2026-03-31',
                'numero_camas' => 60,
                'observacoes' => 'jan.-mar. 2026',
            ],
            [
                'data_inicio' => '2026-04-01',
                'data_fim' => '2026-08-31',
                'numero_camas' => 63,
                'observacoes' => 'abr.-ago. 2026',
            ],
        ];

        foreach ($periodos as $periodo) {
            Lotacao::updateOrCreate(
                ['data_inicio' => $periodo['data_inicio']],
                $periodo
            );
        }
    }
}
