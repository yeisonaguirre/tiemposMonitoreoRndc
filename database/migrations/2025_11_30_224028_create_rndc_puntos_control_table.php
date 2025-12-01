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
        Schema::create('rndc_puntos_control', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rndc_manifiesto_id')->constrained('rndc_manifiestos')->onDelete('cascade');

            $table->integer('codpuntocontrol');
            $table->string('codmunicipio', 20);
            $table->string('direccion', 255);
            $table->date('fechacita');
            $table->string('horacita', 10)->nullable();
            $table->decimal('latitud', 10, 5)->nullable();
            $table->decimal('longitud', 11, 5)->nullable();
            $table->integer('tiempopactado')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rndc_puntos_control');
    }
};
