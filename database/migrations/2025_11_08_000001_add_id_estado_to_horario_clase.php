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
        Schema::table('horario_clase', function (Blueprint $table) {
            // Agregar FK a la tabla estado
            $table->unsignedBigInteger('id_estado')
                  ->after('activo')
                  ->nullable() // Nullable temporalmente para migración
                  ->comment('FK a tabla estado: BORRADOR, APROBADA, PUBLICADA, CANCELADA');
            
            $table->foreign('id_estado')
                  ->references('id_estado')
                  ->on('estado')
                  ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horario_clase', function (Blueprint $table) {
            $table->dropForeign(['id_estado']);
            $table->dropColumn('id_estado');
        });
    }
};
