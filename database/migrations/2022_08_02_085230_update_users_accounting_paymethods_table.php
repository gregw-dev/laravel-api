<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUsersAccountingPaymethodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("users_accounting_paymethods", function (Blueprint $objTable) {
            $objTable->string("paymethod_type")->after("paypal")->index("idx_paymethod-type");
            $objTable->renameColumn("paypal", "paymethod_account");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("users_accounting_paymethods", function (Blueprint $objTable) {
            $objTable->renameColumn("paymethod_account", "paypal");
            $objTable->dropColumn("paymethod_type");
        });
    }
}
