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
        Schema::create('rndc_manifiestos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ingresoidmanifiesto')->unique();
            $table->string('numnitempresatransporte', 20);
            $table->date('fechaexpedicionmanifiesto');
            $table->string('codigoempresa', 10)->nullable();
            $table->string('nummanifiestocarga', 50);
            $table->string('numplaca', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rndc_manifiestos');
    }
};
