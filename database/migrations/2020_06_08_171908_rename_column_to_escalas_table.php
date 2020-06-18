<?php

namespace App\database\migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameColumnToEscalasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('escalas', function (Blueprint $table) {
            $table->renameColumn('assinaturaresonsavel', 'assinaturaresponsavel');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('escalas', function (Blueprint $table) {
            $table->renameColumn('assinaturaresponsavel', 'assinaturaresonsavel');
        });
    }
}
