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
    protected array $blocos;

    public function import(string $path): array
    {
        $this->tiposCirurgia = TipoDeCirurgia::pluck('id', 'nome')->toArray();
        $this->internamentos = Internamento::pluck('id', 'episodio')->toArray();
        $this->procedimentos = Procedimento::pluck('id', 'codigo')->toArray();
        // Pré-carregado por bloco_num para evitar uma query de lookup por
        // cada linha do Excel (uma menos por linha, tal como já se fazia
        // para tipos de cirurgia, internamentos e procedimentos).
        $this->blocos = BlocoOperatorio::whereNotNull('bloco_num')->pluck('id', 'bloco_num')->toArray();

        $importados = 0;
        $erros = [];

        SimpleExcelReader::create($path)
            ->getRows()
            ->each(function ($row, $index) use (&$importados, &$erros) {

                try {
                    $this->validarLinha($row);


                    DB::transaction(function () use ($row, &$importados) {

                        $internamentoId = $this->obterInternamentoId($row);

                        $tipoCirurgiaId = $this->obterTipoCirurgia($row);

                        $blocoOperatorio = $this->criarOuAtualizarBloco($row, $internamentoId, $tipoCirurgiaId);

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
        //if (empty($row['NUM_EPISODIO'])) {
        //    throw new \Exception('NUM_EPISODIO em falta.');
        //}

        if (empty($row['BLO_NUM_REG'])) {
            throw new \Exception('BLO_NUM_REG em falta.');
        }

        if (empty($row['DTA_INTERVENCAO'])) {
            throw new \Exception('Data de intervenção em falta.');
        }
    }

    private function obterInternamentoId(array $row): ?int
    {
        $episodio = $row['NUM_EPISODIO'] ?? null;

        // Se não houver episódio → é ambulatório → sem internamento
        if (!$episodio) {
            return null;
        }

        // Se existir episódio mas não existir internamento → também é ambulatório
        // O id já vem pré-carregado em memória (ver import()) — não há necessidade
        // de ir buscar o modelo completo à base de dados aqui.
        return $this->internamentos[$episodio] ?? null;
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

    private function criarOuAtualizarBloco(array $row, ?int $internamentoId, int $tipoCirurgiaId): BlocoOperatorio
    {
        $blocoNum = $row['BLO_NUM_REG'];
        $blocoId = $this->blocos[$blocoNum] ?? null;

        if ($blocoId) {
            $bloco = BlocoOperatorio::findOrFail($blocoId);

            $bloco->update([
                'numero_processo'      => $row['NUM_PROCESSO'] ?? null,
                'internamento_id'     => $internamentoId,
                'tipo_de_cirurgia_id' => $tipoCirurgiaId,
                'ambulatorio'         => $row['CIR_AMB'] ?? 'N',
                'data_intervencao'    => $row['DTA_INTERVENCAO'],
            ]);

            return $bloco;
        }

        $bloco = BlocoOperatorio::create([
            'numero_processo'      => $row['NUM_PROCESSO'] ?? null,
            'internamento_id'     => $internamentoId,
            'tipo_de_cirurgia_id' => $tipoCirurgiaId,
            'ambulatorio'         => $row['CIR_AMB'] ?? 'N',
            'bloco_num'           => $blocoNum,
            'data_intervencao'    => $row['DTA_INTERVENCAO'],
        ]);

        $this->blocos[$blocoNum] = $bloco->id;

        return $bloco;
    }


    private function addProcedimentos(array $row, BlocoOperatorio $blocoOperatorio): void
    {
        if (empty($row['COD_INTERV_CIRURGICA']) && empty($row['PROCEDIMENTO PRINCIPAL'])) {
            return;
        }

        $codigos = preg_split('/[;,]/', $row['COD_INTERV_CIRURGICA']);

        $nomePrincipal = trim($row['PROCEDIMENTO PRINCIPAL'] ?? '');;

        $procedimentoIds = [];

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

            $procedimentoIds[] = $procedimentoId;
        }

        if (!empty($procedimentoIds)) {
            // Um único sync com todos os ids da linha, em vez de um por código
            // (cada syncWithoutDetaching faz o seu próprio round-trip à BD).
            $blocoOperatorio->procedimentos()->syncWithoutDetaching($procedimentoIds);
        }
    }
}
