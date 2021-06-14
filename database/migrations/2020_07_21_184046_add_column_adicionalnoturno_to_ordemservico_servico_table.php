<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnAdicionalnoturnoToOrdemservicoServicoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ordemservico_servico', function (Blueprint $table) {
            $table->float('adicionalnoturno')->after('valor');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ordemservico_servico', function (Blueprint $table) {
            $table->dropColumn('adicionalnoturno');
        });
    }
}
