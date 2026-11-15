<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Doentes com alta até 31-03-2026 ficam com estado "feito". A data de
     * alta considerada é a saída efetiva (`data_saida`) ou, na sua falta,
     * a alta clínica (`data_alta`).
     */
    public function up(): void
    {
        if (!Schema::hasTable('internamentos') || !Schema::hasColumn('internamentos', 'estado')) {
            return;
        }

        DB::table('internamentos')
            ->whereRaw('COALESCE(data_saida, data_alta) <= ?', ['2026-03-31 23:59:59'])
            ->update(['estado' => 'feito']);
    }

    public function down(): void
    {
        // Irreversível: não é possível saber que estado tinham antes.
    }
};
