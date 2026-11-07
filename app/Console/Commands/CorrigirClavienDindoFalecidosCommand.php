<?php

namespace App\Console\Commands;

use App\Models\ClavienDindo;
use App\Models\Internamento;
use Illuminate\Console\Command;

class CorrigirClavienDindoFalecidosCommand extends Command
{
    protected $signature = 'app:corrigir-clavien-dindo-falecidos
        {--apply : Sem esta opção, só mostra o relatório (dry-run).}';

    protected $description = 'Corrige o clavien_dindo_id (para Grau V) dos internamentos com bloco operatório e destino Falecido que não o têm — ver InternamentoController::update.';

    public function handle(): int
    {
        $apply = $this->option('apply');

        $query = Internamento::where('falecido', 1)
            ->whereHas('blocoOperatorios')
            ->where(function ($w) {
                $w->whereNull('clavien_dindo_id')
                    ->orWhere('clavien_dindo_id', '!=', ClavienDindo::GRAU_V_ID);
            });

        $internamentos = $query->get(['id', 'clavien_dindo_id']);

        if ($internamentos->isEmpty()) {
            $this->info('Nenhum internamento a corrigir.');

            return self::SUCCESS;
        }

        $this->warn('Internamentos com bloco operatório e destino Falecido sem Clavien-Dindo Grau V:');
        $this->table(
            ['id', 'clavien_dindo_id atual'],
            $internamentos->map(fn ($i) => [$i->id, $i->clavien_dindo_id ?? 'null'])->toArray()
        );

        if (!$apply) {
            $this->comment('Dry-run — nada foi alterado. Corre novamente com --apply para gravar.');

            return self::SUCCESS;
        }

        $n = Internamento::whereIn('id', $internamentos->pluck('id'))
            ->update(['clavien_dindo_id' => ClavienDindo::GRAU_V_ID]);

        $this->info("{$n} internamento(s) atualizado(s) para Clavien-Dindo Grau V.");

        return self::SUCCESS;
    }
}
