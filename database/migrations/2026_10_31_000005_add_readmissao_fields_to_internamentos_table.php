<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ver nota em `add_destino_detalhe_and_servico_destino_...`: ALTER
     * idempotente, nunca toca em `internamentos` se as colunas já existirem.
     *
     * `causas_readmissao` já existia; estes campos complementam-na com o
     * acompanhamento pós-alta usado para perceber se a readmissão era
     * evitável (consulta/contacto telefónico agendados) e se era planeada
     * ou relacionada com o internamento anterior.
     */
    public function up(): void
    {
        $colunas = [
            'readmissao_planeada' => fn (Blueprint $t) => $t->boolean('readmissao_planeada')->nullable()->after('causas_readmissao'),
            'readmissao_relacionada' => fn (Blueprint $t) => $t->boolean('readmissao_relacionada')->nullable()->after('readmissao_planeada'),
            'consulta_pos_alta_marcada' => fn (Blueprint $t) => $t->boolean('consulta_pos_alta_marcada')->nullable()->after('readmissao_relacionada'),
            'data_consulta_pos_alta' => fn (Blueprint $t) => $t->date('data_consulta_pos_alta')->nullable()->after('consulta_pos_alta_marcada'),
            'contacto_telefonico_pos_alta' => fn (Blueprint $t) => $t->boolean('contacto_telefonico_pos_alta')->nullable()->after('data_consulta_pos_alta'),
            'data_contacto_pos_alta' => fn (Blueprint $t) => $t->date('data_contacto_pos_alta')->nullable()->after('contacto_telefonico_pos_alta'),
        ];

        foreach ($colunas as $nome => $callback) {
            if (Schema::hasTable('internamentos') && !Schema::hasColumn('internamentos', $nome)) {
                Schema::table('internamentos', function (Blueprint $table) use ($callback) {
                    $callback($table);
                });
            }
        }
    }

    public function down(): void
    {
        $colunas = [
            'data_contacto_pos_alta',
            'contacto_telefonico_pos_alta',
            'data_consulta_pos_alta',
            'consulta_pos_alta_marcada',
            'readmissao_relacionada',
            'readmissao_planeada',
        ];

        foreach ($colunas as $nome) {
            if (Schema::hasTable('internamentos') && Schema::hasColumn('internamentos', $nome)) {
                Schema::table('internamentos', function (Blueprint $table) use ($nome) {
                    $table->dropColumn($nome);
                });
            }
        }
    }
};
