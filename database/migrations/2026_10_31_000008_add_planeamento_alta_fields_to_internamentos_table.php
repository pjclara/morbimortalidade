<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ALTER idempotente (ver nota nas migrations anteriores de
     * internamentos). `data_prevista_alta`/`hora_alta` permitem planear a
     * alta com antecedência; `motivo_nao_antecipacao_alta_id` regista por
     * que razão a alta não saiu mais cedo quando a prevista não se cumpriu.
     */
    public function up(): void
    {
        if (Schema::hasTable('internamentos') && !Schema::hasColumn('internamentos', 'data_prevista_alta')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->date('data_prevista_alta')->nullable()->after('data_alta');
            });
        }

        if (Schema::hasTable('internamentos') && !Schema::hasColumn('internamentos', 'hora_alta')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->time('hora_alta')->nullable()->after('data_prevista_alta');
            });
        }

        if (Schema::hasTable('internamentos') && !Schema::hasColumn('internamentos', 'motivo_nao_antecipacao_alta_id')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->unsignedBigInteger('motivo_nao_antecipacao_alta_id')->nullable()->after('hora_alta');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('internamentos') && Schema::hasColumn('internamentos', 'motivo_nao_antecipacao_alta_id')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->dropColumn('motivo_nao_antecipacao_alta_id');
            });
        }

        if (Schema::hasTable('internamentos') && Schema::hasColumn('internamentos', 'hora_alta')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->dropColumn('hora_alta');
            });
        }

        if (Schema::hasTable('internamentos') && Schema::hasColumn('internamentos', 'data_prevista_alta')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->dropColumn('data_prevista_alta');
            });
        }
    }
};
