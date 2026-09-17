<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Nota: o esquema desta tabela não é gerido por migrations neste
     * projeto (foi criado diretamente na base de dados), por isso esta
     * migration serve sobretudo de registo da alteração; a coluna é
     * adicionada apenas se ainda não existir.
     *
     * `momento` regista quando surgiu a complicação em relação à alta:
     * 'antes_alta', 'ate_30_dias', '31_90_dias' ou 'mais_90_dias'
     * (ver MOMENTO_OPTIONS no InternamentoController).
     */
    public function up(): void
    {
        if (Schema::hasTable('complicacao_internamento') && !Schema::hasColumn('complicacao_internamento', 'momento')) {
            Schema::table('complicacao_internamento', function (Blueprint $table) {
                $table->string('momento', 20)->nullable()->after('complicacao_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('complicacao_internamento') && Schema::hasColumn('complicacao_internamento', 'momento')) {
            Schema::table('complicacao_internamento', function (Blueprint $table) {
                $table->dropColumn('momento');
            });
        }
    }
};
