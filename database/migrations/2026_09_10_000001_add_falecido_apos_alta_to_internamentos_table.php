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
     * `falecido` continua a marcar quem faleceu no serviço, antes da alta
     * (tipicamente com destino "Falecido"). `falecido_apos_alta` identifica,
     * em separado, quem teve alta com vida mas veio a falecer depois.
     */
    public function up(): void
    {
        if (Schema::hasTable('internamentos') && !Schema::hasColumn('internamentos', 'falecido_apos_alta')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->boolean('falecido_apos_alta')->default(false)->after('falecido');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('internamentos') && Schema::hasColumn('internamentos', 'falecido_apos_alta')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->dropColumn('falecido_apos_alta');
            });
        }
    }
};
