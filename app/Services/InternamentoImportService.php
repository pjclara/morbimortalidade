<?php

namespace App\Services;

use App\Models\Destino;
use App\Models\Internamento;
use App\Models\Origem;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;


class InternamentoImportService
{
    protected array $destinos;
    protected array $origens;
    protected array $episodios;

    public function import(string $path): array
    {
        $this->destinos = Destino::pluck('id', 'nome')->toArray();
        $this->origens = Origem::pluck('id', 'id')->toArray();
        $this->episodios = Internamento::pluck('id', 'episodio')->toArray();

        $importados = 0;
        $erros = [];


        SimpleExcelReader::create($path)
            ->getRows()
            ->each(function ($row, $index) use (&$importados, &$erros) {

                try {

                    $this->validarLinha($row);


                    if (isset($this->episodios[$row['INT_EPISODIO']])) {
                        return;
                    }

                    DB::transaction(function () use ($row, $importados) {
                    
                        $patient = $this->obterPaciente($row);
                        $this->criarInternamento($row, $patient);
                        $importados++;

                    });

                } catch (\Throwable $e) {

                    $erros[] = "Linha {$index}: {$e->getMessage()}";
                }
            });

        return [
            'imported' => $importados,
            'errors' => $erros,
        ];
    }

    private function validarLinha(array $row): void
    {
        if (empty($row['NUM_PROCESSO'])) {
            throw new \Exception('NUM_PROCESSO em falta.');
        }

        if (empty($row['INT_EPISODIO'])) {
            throw new \Exception('INT_EPISODIO em falta.');
        }

        if (empty($row['Dta_Alta'])) {
            throw new \Exception('Data de alta em falta.');
        }
    }

    private function obterPaciente(array $row): Patient
    {
        return Patient::firstOrCreate(
            ['processo' => $row['NUM_PROCESSO']],
            [
                'data_nascimento' => $row['DTA_NASCIMENTO'],
                'sexo_id'         => $row['SEXO'],
            ]
        );
    }

    private function criarInternamento(array $row, Patient $patient): void
    {
        $destinoId = $this->destinos[$row['Alta']] ?? null;
        $origemId = $this->origens[$row['COD_PROVENIENCIA']] ?? 99;

        Internamento::create([
            'patient_id'          => $patient->id,
            'episodio'            => $row['INT_EPISODIO'],
            'data_entrada'        => $row['DTA_INTERNAMENTO'],
            'data_saida'          => $row['Dta_Alta'],
            'data_alta'           => null,
            'dias_internamento'   => $row['DIAS INT'],
            'destino_id'          => $destinoId,
            'origem_id'           => $origemId,
            'falecido'            => $destinoId == 3,
        ]);

        // Atualiza a cache para evitar importar o mesmo episódio novamente
        $this->episodios[$row['INT_EPISODIO']] = true;
    }
}
