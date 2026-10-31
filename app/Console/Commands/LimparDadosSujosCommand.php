<?php

namespace App\Console\Commands;

use App\Models\BlocoOperatorio;
use App\Models\Internamento;
use App\Models\Lotacao;
use App\Models\Origem;
use App\Models\TipoDeCirurgia;
use Illuminate\Console\Command;

class LimparDadosSujosCommand extends Command
{
    protected $signature = 'app:limpar-dados-sujos
        {--apply : Sem esta opção, só mostra o relatório (dry-run).}
        {--lotacoes-2026-09 : Insere o período de lotação a partir de 2026-09-01 em vez de limpar tipo_de_cirurgias/origems.}
        {--numero-camas= : Obrigatório com --lotacoes-2026-09.}';

    protected $description = 'Relatório (e, com --apply, correção) de dados sujos em tipo_de_cirurgias/origems, ou inserção do período de lotação a partir de 2026-09-01.';

    /**
     * Mapeamento "nome sujo → nome correto" para tipo_de_cirurgias. Fica
     * vazio de propósito: a correção de cada id tem de ser confirmada com o
     * serviço antes de correr com --apply (ver relatório --dry-run).
     *
     * Exemplo de preenchimento depois de confirmado:
     * [15 => 'Colecistectomia', 23 => 'Apendicectomia']
     */
    protected array $mapeamentoTipoDeCirurgia = [
        //
    ];

    /**
     * Idem para origems — o nome '13' não identifica nada por si só.
     * Exemplo: [13 => 'Consulta Externa']
     */
    protected array $mapeamentoOrigem = [
        //
    ];

    public function handle(): int
    {
        if ($this->option('lotacoes-2026-09')) {
            return $this->tratarLotacoes();
        }

        return $this->tratarNomesSujos();
    }

    protected function tratarLotacoes(): int
    {
        $numeroCamas = $this->option('numero-camas');

        if ($numeroCamas === null) {
            $this->error('--numero-camas é obrigatório com --lotacoes-2026-09.');

            return self::FAILURE;
        }

        $periodo = [
            'data_inicio' => '2026-09-01',
            'data_fim' => null,
            'numero_camas' => (int) $numeroCamas,
            'observacoes' => 'a partir de set. 2026',
        ];

        if (!$this->option('apply')) {
            $this->info('Dry-run — período que seria inserido/atualizado em lotacoes:');
            $this->table(array_keys($periodo), [array_values($periodo)]);
            $this->comment('Corre novamente com --apply para gravar.');

            return self::SUCCESS;
        }

        Lotacao::updateOrCreate(['data_inicio' => $periodo['data_inicio']], $periodo);
        $this->info('Período de lotação a partir de 2026-09-01 gravado.');

        return self::SUCCESS;
    }

    protected function tratarNomesSujos(): int
    {
        $apply = $this->option('apply');

        $this->limparTabela(
            TipoDeCirurgia::query(),
            fn (string $nome) => $nome === 'nan' || preg_match('/^\d+\.0$/', $nome) === 1,
            fn (int $id) => BlocoOperatorio::where('tipo_de_cirurgia_id', $id)->count(),
            $this->mapeamentoTipoDeCirurgia,
            'tipo_de_cirurgias',
            $apply
        );

        $this->limparTabela(
            Origem::query(),
            fn (string $nome) => $nome === '13',
            fn (int $id) => Internamento::where('origem_id', $id)->count(),
            $this->mapeamentoOrigem,
            'origems',
            $apply
        );

        return self::SUCCESS;
    }

    protected function limparTabela($query, callable $invalido, callable $contarUsos, array $mapeamento, string $nomeTabela, bool $apply): void
    {
        $invalidos = $query->get()->filter(fn ($registo) => $invalido((string) $registo->nome));

        if ($invalidos->isEmpty()) {
            $this->info("Nenhum nome inválido encontrado em {$nomeTabela}.");

            return;
        }

        $linhas = $invalidos->map(function ($registo) use ($contarUsos, $mapeamento) {
            return [
                $registo->id,
                $registo->nome,
                $contarUsos($registo->id),
                $mapeamento[$registo->id] ?? '(sem mapeamento — confirmar)',
            ];
        });

        $this->warn("Nomes inválidos em {$nomeTabela}:");
        $this->table(['id', 'nome atual', 'registos que usam este id', 'proposta'], $linhas->toArray());

        if (!$apply) {
            $this->comment('Dry-run — nada foi alterado. Preencha o mapeamento no comando e corra com --apply.');

            return;
        }

        foreach ($invalidos as $registo) {
            if (!isset($mapeamento[$registo->id])) {
                $this->warn("id {$registo->id} sem mapeamento definido — ignorado.");

                continue;
            }

            $registo->update(['nome' => $mapeamento[$registo->id]]);
            $this->info("id {$registo->id} atualizado para '{$mapeamento[$registo->id]}'.");
        }
    }
}
