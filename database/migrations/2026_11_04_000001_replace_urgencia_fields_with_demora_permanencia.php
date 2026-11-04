<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Substitui `data_admissao_urgencia` + `episodio_urgencia` (e o accessor
     * derivado `dias_permanencia_urgencia`) por `demora_permanencia_urgencia`,
     * carregada diretamente da coluna DEMORA_PERMANECE_URG do export do SONHO
     * em vez de ser calculada — ver InternamentoImportService.
     */
    public function up(): void
    {
        if (Schema::hasTable('internamentos') && !Schema::hasColumn('internamentos', 'demora_permanencia_urgencia')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->integer('demora_permanencia_urgencia')->nullable()->after('data_entrada');
            });
        }

        if (Schema::hasTable('internamentos') && Schema::hasColumn('internamentos', 'episodio_urgencia')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->dropColumn('episodio_urgencia');
            });
        }

        if (Schema::hasTable('internamentos') && Schema::hasColumn('internamentos', 'data_admissao_urgencia')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->dropColumn('data_admissao_urgencia');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('internamentos') && !Schema::hasColumn('internamentos', 'data_admissao_urgencia')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->date('data_admissao_urgencia')->nullable()->after('data_entrada');
            });
        }

        if (Schema::hasTable('internamentos') && !Schema::hasColumn('internamentos', 'episodio_urgencia')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->integer('episodio_urgencia')->nullable()->after('data_admissao_urgencia');
            });
        }

        if (Schema::hasTable('internamentos') && Schema::hasColumn('internamentos', 'demora_permanencia_urgencia')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->dropColumn('demora_permanencia_urgencia');
            });
        }
    }
};
