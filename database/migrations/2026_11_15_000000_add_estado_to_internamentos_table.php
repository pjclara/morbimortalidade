<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ALTER idempotente (ver nota nas migrations anteriores de
     * internamentos). Estado de preenchimento do internamento:
     * para_fazer | parcialmente_feito | feito.
     */
    public function up(): void
    {
        if (Schema::hasTable('internamentos') && !Schema::hasColumn('internamentos', 'estado')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->string('estado', 30)->default('para_fazer');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('internamentos') && Schema::hasColumn('internamentos', 'estado')) {
            Schema::table('internamentos', function (Blueprint $table) {
                $table->dropColumn('estado');
            });
        }
    }
};
