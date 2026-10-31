<?php

namespace Database\Seeders;

use App\Models\MotivoAtraso;
use Illuminate\Database\Seeder;

class MotivoAtrasoSeeder extends Seeder
{
    public function run(): void
    {
        $motivos = [
            'Social/sem retaguarda familiar',
            'Aguarda vaga na rede de cuidados continuados',
            'Aguarda vaga em lar/ERPI',
            'Aguarda transporte',
            'Aguarda exames/procedimentos',
            'Decisão da família',
            'Intercorrência clínica',
            'Outro',
        ];

        foreach ($motivos as $nome) {
            MotivoAtraso::updateOrCreate(['nome' => $nome], ['ativo' => true]);
        }
    }
}
