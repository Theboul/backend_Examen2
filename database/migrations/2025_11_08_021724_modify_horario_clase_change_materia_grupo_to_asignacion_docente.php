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
            // Eliminar la columna id_materia_grupo (no tiene FK)
            $table->dropColumn('id_materia_grupo');
            
            // Agregar la columna id_asignacion_docente después de id_horario_clase
            $table->integer('id_asignacion_docente')->after('id_horario_clase');
            
            // Agregar foreign key a asignacion_docente
            $table->foreign('id_asignacion_docente')
                  ->references('id_asignacion_docente')
                  ->on('asignacion_docente')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horario_clase', function (Blueprint $table) {
            // Eliminar foreign key de id_asignacion_docente
            $table->dropForeign(['id_asignacion_docente']);
            
            // Eliminar columna id_asignacion_docente
            $table->dropColumn('id_asignacion_docente');
            
            // Restaurar columna id_materia_grupo
            $table->integer('id_materia_grupo')->after('id_horario_clase');
        });
    }
};
