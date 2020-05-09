<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCuidadosGrupocuidadosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cuidados_grupocuidados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuidado')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('grupo');
            $table->foreign('grupo')->references('id')->on('grupocuidados')->onDelete('cascade');
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
        Schema::dropIfExists('cuidados_grupocuidados');
    }
}