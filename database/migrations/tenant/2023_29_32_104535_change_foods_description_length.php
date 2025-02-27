<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeFoodsDescriptionLength extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('foods',function (Blueprint $table){
            $table->string('description',500)->nullable()->change();
        });

    }



    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('foods',function (Blueprint $table){
            $table->string('description',255)->nullable()->change();
        });
      

    }
}
