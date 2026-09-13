<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A página de Analítica filtra e agrega sempre por período — sem índice
     * nestas colunas, cada pedido fazia uma leitura completa das tabelas.
     */
    public function up(): void
    {
        Schema::table('internamentos', function (Blueprint $table) {
            $table->index('data_entrada');
        });

        Schema::table('bloco_operatorios', function (Blueprint $table) {
            $table->index('data_intervencao');
        });

        Schema::table('diagnostico_internamento', function (Blueprint $table) {
            $table->index('principal');
        });
    }

    public function down(): void
    {
        Schema::table('internamentos', function (Blueprint $table) {
            $table->dropIndex(['data_entrada']);
        });

        Schema::table('bloco_operatorios', function (Blueprint $table) {
            $table->dropIndex(['data_intervencao']);
        });

        Schema::table('diagnostico_internamento', function (Blueprint $table) {
            $table->dropIndex(['principal']);
        });
    }
};
