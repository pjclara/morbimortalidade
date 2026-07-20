<?php

namespace App\Services;

use App\Models\BlocoOperatorio;
use App\Models\Internamento;
use App\Models\Procedimento;
use App\Models\TipoDeCirurgia;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class BlocoOperatorioImportService
{
    protected array $tiposCirurgia;
    protected array $internamentos;
    protected array $procedimentos;

    public function import(string $path): array
    {
        $this->tiposCirurgia = TipoDeCirurgia::pluck('id', 'nome')->toArray();
        $this->internamentos = Internamento::pluck('id', 'episodio')->toArray();
        $this->procedimentos = Procedimento::pluck('id', 'codigo')->toArray();

        $importados = 0;
        $erros = [];

        SimpleExcelReader::create($path)
            ->getRows()
            ->each(function ($row, $index) use (&$importados, &$erros) {

                try {
                    $this->validarLinha($row);


                    DB::transaction(function () use ($row, &$importados) {

                        $internamento = $this->obterInternamento($row);

                        $tipoCirurgiaId = $this->obterTipoCirurgia($row);


                        $blocoOperatorio = $this->criarOuAtualizarBloco($row, $internamento, $tipoCirurgiaId);
                        

                        $this->addProcedimentos($row, $blocoOperatorio);

                        $importados++;
                    });
                } catch (\Throwable $e) {
                    $erros[] = "Linha {$index}: {$e->getMessage()}";
                }
            });

        return [
            'imported' => $importados,
            'errors'   => $erros,
        ];
    }

    private function validarLinha(array $row): void
    {
        if (empty($row['NUM_EPISODIO'])) {
            throw new \Exception('NUM_EPISODIO em falta.');
        }

        if (empty($row['BLO_NUM_REG'])) {
            throw new \Exception('BLO_NUM_REG em falta.');
        }

        if (empty($row['DTA_INTERVENCAO'])) {
            throw new \Exception('Data de intervenção em falta.');
        }
    }

    private function obterInternamento(array $row): Internamento
    {
        $episodio = $row['NUM_EPISODIO'];

        if (!isset($this->internamentos[$episodio])) {
            throw new \Exception("Internamento não encontrado para episódio {$episodio}");
        }

        return Internamento::find($this->internamentos[$episodio]);
    }

    private function obterTipoCirurgia(array $row): int
    {
        $nome = trim($row['DES_TIPO_CIRURGIA']);
        $codigo = trim($row['COD_INTERV_CIRURGICA'] ?? $nome);

        if ($nome === '') {
            throw new \Exception('DES_TIPO_CIRURGIA vazio.');
        }

        if (isset($this->tiposCirurgia[$nome])) {
            return $this->tiposCirurgia[$nome];
        }

        $tipo = TipoDeCirurgia::create([
            'codigo' => $codigo,
            'nome'   => $nome,
        ]);

        $this->tiposCirurgia[$nome] = $tipo->id;

        return $tipo->id;
    }

    private function criarOuAtualizarBloco(array $row, Internamento $internamento, int $tipoCirurgiaId): BlocoOperatorio
    {
        $bloco = BlocoOperatorio::where('bloco_num', $row['BLO_NUM_REG'])->first();

        if ($bloco) {
            $bloco->update([
                'internamento_id'     => $internamento->id,
                'tipo_de_cirurgia_id' => $tipoCirurgiaId,
                'ambulatorio'         => $row['CIR_AMB'] ?? 'N',
                'data_intervencao'    => $row['DTA_INTERVENCAO'],
            ]);

            return $bloco;
        }

        return BlocoOperatorio::create([
            'internamento_id'     => $internamento->id,
            'tipo_de_cirurgia_id' => $tipoCirurgiaId,
            'ambulatorio'         => $row['CIR_AMB'] ?? 'N',
            'bloco_num'           => $row['BLO_NUM_REG'],
            'data_intervencao'    => $row['DTA_INTERVENCAO'],
        ]);
    }

    private function addProcedimentos(array $row, BlocoOperatorio $blocoOperatorio): void
    {
        if (empty($row['COD_INTERV_CIRURGICA']) && empty($row['PROCEDIMENTO PRINCIPAL'])) {
            return;
        }
        
        $codigos = preg_split('/[;,]/', $row['COD_INTERV_CIRURGICA']);
        
        $nomePrincipal = trim($row['PROCEDIMENTO PRINCIPAL'] ?? '');
;

        foreach ($codigos as $codigoRaw) {

            $codigo = trim($codigoRaw);
            if ($codigo === '') {
                continue;
            }

            $procedimentoId = $this->procedimentos[$codigo] ?? null;

            if (!$procedimentoId) {
                $procedimento = Procedimento::create([
                    'codigo' => $codigo,
                    'nome'   => $nomePrincipal !== '' ? $nomePrincipal : $codigo,
                ]);

                $procedimentoId = $procedimento->id;
                $this->procedimentos[$codigo] = $procedimentoId;
            }

            $blocoOperatorio->procedimentos()->syncWithoutDetaching([$procedimentoId]);

        }
    }
}
