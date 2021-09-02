<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndicacaoClinicaToOrcsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orcs', function (Blueprint $table) {
            $table->string('indicacaoClinica')->nullable()->after('versao');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orcs', function (Blueprint $table) {
            $table->dropColumn('indicacaoClinica');
        });
    }
}
