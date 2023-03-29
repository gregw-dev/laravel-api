<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSoundblockPaymentsMusicUsersBalanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("soundblock_payments_music_users_balance", function (Blueprint $objTable) {
            $objTable->json("withdrawal_method_data")->after("withdrawal_method");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("soundblock_payments_music_users_balance", function (Blueprint $objTable) {
            $objTable->dropColumn("withdrawal_method_data");
        });
    }
}
