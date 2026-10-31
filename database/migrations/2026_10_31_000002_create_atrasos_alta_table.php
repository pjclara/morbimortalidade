<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('atrasos_alta')) {
            return;
        }

        Schema::create('atrasos_alta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internamento_id')->constrained('internamentos')->cascadeOnDelete();
            $table->foreignId('motivo_id')->constrained('motivos_atraso');
            $table->date('data_sinalizacao_servico_social')->nullable();
            $table->date('data_pedido_rede')->nullable();
            $table->string('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atrasos_alta');
    }
};
