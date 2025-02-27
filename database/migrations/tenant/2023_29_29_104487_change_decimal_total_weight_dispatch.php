<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDecimalTotalWeightDispatch extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {


        Schema::table('dispatches', function (Blueprint $table) {
            $table->decimal('total_weight', 18, 8)->change();
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
        Schema::table('dispatches', function (Blueprint $table) {
            $table->decimal('total_weight', 10, 2)->change();
        });
      
    }
}
