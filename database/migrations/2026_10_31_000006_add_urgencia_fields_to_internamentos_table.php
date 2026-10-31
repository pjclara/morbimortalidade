<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ALTER idempotente (ver nota nas migrations anteriores de
     * internamentos). `data_admissao_urgencia` e `episodio_urgencia` vêm do
     * export do SONHO (colunas DTA_ADMISSAO e URG_EPISODIO) — ver
     * InternamentoImportService. A "Demora Permanece Urg" do SONHO não é
     * guardada à parte: é derivável destas duas colunas + data_entrada, por
     * isso fica como accessor (`dias_permanencia_urgencia`) em vez de coluna.
     */
    public function up(): void
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
    }

    public function down(): void
    {
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
};
