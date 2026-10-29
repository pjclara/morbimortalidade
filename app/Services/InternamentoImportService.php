<?php

namespace App\Services;

use App\Models\Destino;
use App\Models\Diagnostico;
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
    protected array $diagnosticos;


    public function import(string $path): array
    {
        $this->destinos = Destino::pluck('id', 'nome')->toArray();
        $this->origens = Origem::pluck('id', 'id')->toArray();
        $this->episodios = Internamento::pluck('id', 'episodio')->toArray();
        $this->diagnosticos = Diagnostico::pluck('id', 'codigo')->toArray();


        $importados = 0;
        $erros = [];


        SimpleExcelReader::create($path)
            ->getRows()
            ->each(function ($row, $index) use (&$importados, &$erros) {

                try {

                    $this->validarLinha($row);


                    $internamentoId = $this->episodios[$row['INT_EPISODIO']] ?? null;

                    if ($internamentoId) {
                        // Internamento já existe: não o recriar, só atualizar
                        // os diagnósticos com o que vier nesta linha (e
                        // preencher a data de alta clínica, se ainda faltar).
                        DB::transaction(function () use ($row, $internamentoId, &$importados) {
                            $internamento = Internamento::findOrFail($internamentoId);

                            if (!$internamento->data_alta && !empty($row['DTA_ALTA_CLINICA'])) {
                                $internamento->update(['data_alta' => $row['DTA_ALTA_CLINICA']]);
                            }

                            $this->addDiagnosticos($row, $internamento);

                            $importados++;
                        });

                        return;
                    }

                    DB::transaction(function () use ($row, &$importados) {

                        $patient = $this->obterPaciente($row);
                        $internamento = $this->criarInternamento($row, $patient);

                        // 🔥 adicionar diagnósticos
                        $this->addDiagnosticos($row, $internamento);

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

    private function criarInternamento(array $row, Patient $patient): Internamento
    {
        $destinoId = $this->destinos[$row['Alta']] ?? null;
        $origemId = $this->origens[$row['PROVENIENCIA']] ?? 99;

        $internamento = Internamento::create([
            'patient_id'          => $patient->id,
            'episodio'            => $row['INT_EPISODIO'],
            'data_entrada'        => $row['DTA_INTERNAMENTO'],
            'data_saida'          => $row['Dta_Alta'],
            'data_alta'           => $row['DTA_ALTA_CLINICA'] ?? null,
            'dias_internamento'   => $row['DIAS INT'],
            'destino_id'          => $destinoId,
            'origem_id'           => $origemId,
            'falecido'            => $destinoId == 3,
        ]);

        // Atualiza a cache com o id real, para que linhas seguintes do mesmo
        // ficheiro com o mesmo episódio consigam encontrar este internamento.
        $this->episodios[$row['INT_EPISODIO']] = $internamento->id;

        return $internamento;
    }

    private function addDiagnosticos(array $row, Internamento $internamento): void
    {

        if (empty($row['COD_DIAGNOSTICO'])) {
            return;
        }

        // Exemplo: "K35.2; K36; K37"
        $lista = explode(';', $row['COD_DIAGNOSTICO']);

        $descricao = trim($row['DES_DIAGNOSTICO'] ?? '');

        // COD_CLASSIFICACAO == 1 identifica o diagnóstico principal do internamento.
        $principal = trim((string) ($row['COD_CLASSIFICACAO'] ?? '')) === '1';

        foreach ($lista as $item) {

            $codigo = trim($item);

            if ($codigo === '') {
                continue;
            }

            // Verificar se já existe na cache
            $diagnosticoId = $this->diagnosticos[$codigo] ?? null;

            // Criar diagnóstico se não existir
            if (!$diagnosticoId) {
                // "nome", "codigo_pai" e "descricao" são NOT NULL sem default na BD.
                // O Excel não traz um código pai, por isso fica vazio.
                $nomeDescricao = $descricao !== '' ? $descricao : $codigo;

                $diagnostico = Diagnostico::create([
                    'codigo'     => $codigo,
                    'codigo_pai' => '',
                    'nome'       => $nomeDescricao,
                    'descricao'  => $nomeDescricao,
                ]);

                // Atualizar cache
                $this->diagnosticos[$codigo] = $diagnostico->id;
                $diagnosticoId = $diagnostico->id;
            }

            // Associar ao internamento (evita duplicados), marcando como
            // principal quando a linha trouxer COD_CLASSIFICACAO == 1.
            $internamento->diagnosticos()->syncWithoutDetaching([
                $diagnosticoId => ['principal' => $principal],
            ]);
        }
    }
}
