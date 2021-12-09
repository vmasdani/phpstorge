<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStorageRecordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('storage_record', function (Blueprint $table) {
            $table->id();
            // basemodel
            $table->text('uuid')->nullable();
            $table->integer('ordering')->nullable();
            $table->bigInteger('ext_created_by_id')->nullable();
            $table->boolean('hidden')->nullable();
            // basemodel end
            $table->bigInteger('created')->nullable();
            $table->bigInteger('updated')->nullable();
            $table->longText('value')->nullable();
            $table->bigInteger('record_id')->nullable(); 

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
        Schema::dropIfExists('storage_record');
    }
}
