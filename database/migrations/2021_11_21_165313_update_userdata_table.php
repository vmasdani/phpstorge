<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUserdataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('userdata', function (Blueprint $table) {
            // basemodel
            $table->text('uuid')->nullable();
            $table->integer('ordering')->nullable();
            $table->bigInteger('ext_created_by_id')->nullable();
            $table->boolean('hidden')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
