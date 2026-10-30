<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nota: o esquema desta tabela não é gerido por migrations neste
     * projeto (foi criado diretamente na base de dados), por isso esta
     * migration serve sobretudo de registo da alteração; a coluna é
     * adicionada apenas se ainda não existir.
     *
     * `causas_readmissao` guarda as causas selecionadas no formulário para
     * um internamento identificado como readmissão (<30 dias após alta
     * anterior), como array json — um internamento pode ter mais do que
     * uma causa associada.
     */
    public function up(): void
    {
        if (Schema::hasTable('internamentos') && !Schema::hasColumn('internamentos', 'causas_readmissao')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->json('causas_readmissao')->nullable()->after('falecido_apos_alta');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('internamentos') && Schema::hasColumn('internamentos', 'causas_readmissao')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->dropColumn('causas_readmissao');
            });
        }
    }
};
