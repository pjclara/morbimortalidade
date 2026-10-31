<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * O esquema de `internamentos` e tabelas relacionadas não é gerido por
     * migrations neste projeto — foi criado diretamente no MySQL de
     * produção (ver as migrations `add_*_to_internamentos_table`). Isso
     * significa que a base de dados de testes (sqlite `:memory:`, só com
     * `RefreshDatabase`) nunca tem estas tabelas, tornando impossível
     * qualquer Feature test para Internamento.
     *
     * Esta migration cria cada tabela só se ainda não existir: em produção
     * (onde já existem) é sempre um no-op; só materializa o esquema numa BD
     * de testes/CI vazia. As colunas replicam exatamente o que os
     * models/controllers/import assumem hoje (`$fillable`, queries, Excel
     * import) — não inventam nada de novo.
     */
    public function up(): void
    {
        if (!Schema::hasTable('sexos')) {
            Schema::create('sexos', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('patients')) {
            Schema::create('patients', function (Blueprint $table) {
                $table->id();
                $table->string('processo')->nullable()->unique();
                $table->date('data_nascimento')->nullable();
                $table->unsignedBigInteger('sexo_id')->nullable();
                $table->unsignedBigInteger('localidade_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('destinos')) {
            Schema::create('destinos', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('origems')) {
            Schema::create('origems', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('equipas')) {
            Schema::create('equipas', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->string('abrv')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('clavien_dindos')) {
            Schema::create('clavien_dindos', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->string('descricao')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('grupo_diagnosticos')) {
            Schema::create('grupo_diagnosticos', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('diagnosticos')) {
            Schema::create('diagnosticos', function (Blueprint $table) {
                $table->id();
                $table->string('codigo');
                $table->string('codigo_pai')->nullable();
                $table->string('nome');
                $table->string('descricao')->nullable();
                $table->unsignedBigInteger('grupo_diagnostico_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('grupo_complicacaos')) {
            Schema::create('grupo_complicacaos', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('complicacaos')) {
            Schema::create('complicacaos', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->unsignedBigInteger('grupo_complicacao_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('resolucaos')) {
            Schema::create('resolucaos', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->string('descricao')->nullable();
                $table->unsignedBigInteger('clavien_dindo_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tipo_de_cirurgias')) {
            Schema::create('tipo_de_cirurgias', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('procedimentos')) {
            Schema::create('procedimentos', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('internamentos')) {
            Schema::create('internamentos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('patient_id')->nullable();
                $table->string('episodio')->nullable();
                $table->date('data_entrada')->nullable();
                $table->date('data_alta')->nullable();
                $table->date('data_saida')->nullable();
                $table->unsignedInteger('dias_internamento')->nullable();
                $table->unsignedBigInteger('destino_id')->nullable();
                $table->unsignedBigInteger('origem_id')->nullable();
                $table->unsignedBigInteger('equipa_id')->nullable();
                $table->unsignedBigInteger('responsavel_id')->nullable();
                $table->unsignedBigInteger('clavien_dindo_id')->nullable();
                $table->boolean('falecido')->default(false);
                $table->boolean('falecido_apos_alta')->default(false);
                $table->boolean('mortalidade_esperada')->nullable();
                $table->string('observacoes')->nullable();
                $table->boolean('bloquear')->nullable();
                $table->json('causas_readmissao')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('diagnostico_internamento')) {
            Schema::create('diagnostico_internamento', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('internamento_id')->nullable();
                $table->unsignedBigInteger('diagnostico_id')->nullable();
                $table->string('descricao')->nullable();
                $table->boolean('principal')->default(false);
            });
        }

        if (!Schema::hasTable('complicacao_internamento')) {
            Schema::create('complicacao_internamento', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('internamento_id')->nullable();
                $table->unsignedBigInteger('complicacao_id')->nullable();
                $table->string('momento', 20)->nullable();
            });
        }

        if (!Schema::hasTable('bloco_operatorios')) {
            Schema::create('bloco_operatorios', function (Blueprint $table) {
                $table->id();
                $table->char('ambulatorio', 1)->nullable();
                $table->string('bloco_num')->nullable();
                $table->date('data_intervencao')->nullable();
                $table->unsignedBigInteger('internamento_id')->nullable();
                $table->string('numero_processo')->nullable();
                $table->unsignedBigInteger('tipo_de_cirurgia_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('bloco_operatorio_procedimento')) {
            Schema::create('bloco_operatorio_procedimento', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bloco_operatorio_id');
                $table->unsignedBigInteger('procedimento_id')->nullable();
                $table->string('descricao');
                $table->timestamps();
            });
        }
    }

    /**
     * Intencionalmente vazio: não há forma segura de saber, aqui, se foi
     * esta migration que criou a tabela (BD de testes) ou se ela já existia
     * em produção — fazer `dropIfExists` às cegas arriscaria apagar dados
     * reais. `up()` só cria o que não existe, nunca o que já está lá.
     */
    public function down(): void
    {
        //
    }
};
