<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfissionalBeneficioTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profissional_beneficio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profissional_id');
            $table->foreign('profissional_id')->references('id')->on('profissionais')->onDelete('cascade');
            $table->foreignId('beneficio_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('profissional_beneficio');
    }
}
