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
        Schema::table('rndc_puntos_control', function (Blueprint $table) {
            $table->date('fecha_llegada')->nullable()->after('tiempopactado');
            $table->string('hora_llegada', 5)->nullable()->after('fecha_llegada');
            $table->date('fecha_salida')->nullable()->after('hora_llegada');
            $table->string('hora_salida', 5)->nullable()->after('fecha_salida');

            $table->timestamp('evento_enviado_at')->nullable()->after('hora_salida');
            $table->string('numero_autorizacion', 100)->nullable()->after('evento_enviado_at');
            $table->boolean('finalizado')->default(false)->after('numero_autorizacion');

            $table->longText('xml_solicitud')->nullable()->after('finalizado');
            $table->longText('xml_respuesta')->nullable()->after('xml_solicitud');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rndc_puntos_control', function (Blueprint $table) {
             $table->dropColumn([
                'fecha_llegada','hora_llegada',
                'fecha_salida','hora_salida',
                'evento_enviado_at','numero_autorizacion',
                'finalizado','xml_solicitud','xml_respuesta',
            ]);
        });
    }
};
