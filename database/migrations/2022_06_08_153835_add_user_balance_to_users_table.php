<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserBalanceToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("users", function (Blueprint $objTable) {
            $objTable->float("user_balance",18,10)->after("last_login")->index("idx_user-balance")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("users", function (Blueprint $objTable) {
            $objTable->dropColumn("user_balance");
            $objTable->dropIndex("idx_user-balance");
        });
    }
}
