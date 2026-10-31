<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ALTER idempotente (ver nota nas migrations anteriores de
     * internamentos). Classificação de complexidade do doente: ASA (1-5),
     * índice de comorbilidade de Charlson, e dias passados em cuidados
     * intermédios/intensivos durante o internamento.
     */
    public function up(): void
    {
        if (Schema::hasTable('internamentos') && !Schema::hasColumn('internamentos', 'asa')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->unsignedTinyInteger('asa')->nullable()->after('clavien_dindo_id');
            });
        }

        if (Schema::hasTable('internamentos') && !Schema::hasColumn('internamentos', 'indice_charlson')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->unsignedTinyInteger('indice_charlson')->nullable()->after('asa');
            });
        }

        if (Schema::hasTable('internamentos') && !Schema::hasColumn('internamentos', 'dias_cuidados_intermedios')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->unsignedSmallInteger('dias_cuidados_intermedios')->nullable()->after('indice_charlson');
            });
        }

        if (Schema::hasTable('internamentos') && !Schema::hasColumn('internamentos', 'dias_cuidados_intensivos')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->unsignedSmallInteger('dias_cuidados_intensivos')->nullable()->after('dias_cuidados_intermedios');
            });
        }
    }

    public function down(): void
    {
        foreach (['dias_cuidados_intensivos', 'dias_cuidados_intermedios', 'indice_charlson', 'asa'] as $coluna) {
            if (Schema::hasTable('internamentos') && Schema::hasColumn('internamentos', $coluna)) {
                Schema::table('internamentos', function (Blueprint $table) use ($coluna) {
                    $table->dropColumn($coluna);
                });
            }
        }
    }
};
