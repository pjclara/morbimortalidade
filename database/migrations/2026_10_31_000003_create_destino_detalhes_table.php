<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('destino_detalhes')) {
            return;
        }

        Schema::create('destino_detalhes', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->foreignId('destino_id')->nullable()->constrained('destinos')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destino_detalhes');
    }
};
