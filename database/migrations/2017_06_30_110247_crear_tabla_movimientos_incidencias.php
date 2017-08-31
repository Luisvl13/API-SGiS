<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaMovimientosIncidencias extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movimientos_incidencias', function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string('id');
            $table->string('servidor_id',4);
            $table->integer('incremento');
            $table->string('incidencias_id');
            $table->string('medico_reporta_id')->nullable();
            $table->string('indicaciones')->nullable();
            $table->string('reporte_medico')->nullable();
            $table->integer('valoraciones_pacientes_id')->unsigned()->nullable();
            $table->integer('estados_pacientes_id')->unsigned()->nullable();
            $table->integer('triage_colores_id')->unsigned()->nullable();
            $table->integer('subcategorias_cie10_id')->unsigned()->nullable();
            $table->integer('turnos_id')->unsigned()->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->primary('id');

            $table->foreign('valoraciones_pacientes_id')->references('id')->on('valoraciones_pacientes')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('estados_pacientes_id')->references('id')->on('estados_pacientes')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('triage_colores_id')->references('id')->on('triage_colores')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('subcategorias_cie10_id')->references('id')->on('subcategorias_cie10')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('turnos_id')->references('id')->on('turnos')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('movimientos_incidencias');
    }
}
