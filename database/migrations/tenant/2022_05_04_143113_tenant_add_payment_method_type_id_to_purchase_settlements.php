<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TenantAddPaymentMethodTypeIdToPurchaseSettlements extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_settlements', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_settlements', 'payment_method_type_id')) {
                $table->char('payment_method_type_id', 2)->nullable()->after('currency_type_id');
                $table->foreign('payment_method_type_id')->references('id')->on('payment_method_types');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_settlements', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_settlements', 'payment_method_type_id')) {
                $table->dropForeign(['payment_method_type_id']);
                $table->dropColumn('payment_method_type_id');
            }
        });
    }
}
