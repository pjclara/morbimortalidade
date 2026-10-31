<?php

namespace Database\Seeders;

use App\Models\DestinoDetalhe;
use Illuminate\Database\Seeder;

class DestinoDetalheSeeder extends Seeder
{
    public function run(): void
    {
        $detalhes = [
            'Domicílio',
            'Domicílio com apoio',
            'Lar/ERPI',
            'Rede de cuidados continuados',
            'Outro hospital',
            'Cuidados intermédios/UCI',
            'Outro serviço do hospital (transferência interna)',
            'Óbito',
        ];

        foreach ($detalhes as $nome) {
            DestinoDetalhe::updateOrCreate(['nome' => $nome]);
        }
    }
}
