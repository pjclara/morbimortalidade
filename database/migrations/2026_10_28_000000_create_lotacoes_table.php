<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // A base de dados de origem é por vezes refrescada/restaurada a
        // partir de um dump que não inclui esta tabela (não gerida pelo
        // Laravel) — tornar idempotente para poder correr sempre em segurança.
        if (Schema::hasTable('lotacoes')) {
            return;
        }

        Schema::create('lotacoes', function (Blueprint $table) {
            $table->id();
            $table->date('data_inicio');
            $table->date('data_fim')->nullable();
            $table->unsignedSmallInteger('numero_camas');
            $table->string('observacoes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotacoes');
    }
};
