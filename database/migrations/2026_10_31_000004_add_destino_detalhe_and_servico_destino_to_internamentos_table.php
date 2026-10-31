<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nota: ver `2026_09_10_000001_add_falecido_apos_alta_to_internamentos_table.php`
     * — o esquema de `internamentos` não é gerido por migrations neste
     * projeto; esta migration só altera a tabela se as colunas ainda não
     * existirem (idempotente, segura para produção).
     *
     * `destino_detalhe_id` detalha o `destino_id` (ex: que tipo de "Alta
     * domicílio" ou de transferência). `servico_destino` só é preenchido
     * quando o destino é uma transferência interna, com o nome do serviço
     * de destino (texto livre — o projeto não tem uma tabela de serviços).
     */
    public function up(): void
    {
        if (Schema::hasTable('internamentos') && !Schema::hasColumn('internamentos', 'destino_detalhe_id')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->unsignedBigInteger('destino_detalhe_id')->nullable()->after('destino_id');
            });
        }

        if (Schema::hasTable('internamentos') && !Schema::hasColumn('internamentos', 'servico_destino')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->string('servico_destino')->nullable()->after('destino_detalhe_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('internamentos') && Schema::hasColumn('internamentos', 'servico_destino')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->dropColumn('servico_destino');
            });
        }

        if (Schema::hasTable('internamentos') && Schema::hasColumn('internamentos', 'destino_detalhe_id')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->dropColumn('destino_detalhe_id');
            });
        }
    }
};
