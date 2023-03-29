<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMemoFieldsToPaymentsProjectsAndUsersTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("soundblock_payments_music_projects", function (Blueprint $objTable) {
            $objTable->string("payment_memo")->after("payment_amount")->nullable()->index("idx_payment-memo");
        });
        Schema::table("soundblock_payments_music_users", function (Blueprint $objTable) {
            $objTable->string("payment_memo")->after("report_revenue_usd")->nullable()->index("idx_payment-memo");
        });
        Schema::table("soundblock_reports_music_matched", function (Blueprint $objTable) {
            $objTable->string("payment_memo")->after("report_revenue_usd")->nullable()->index("idx_payment-memo");
        });
        Schema::table("soundblock_reports_music_unmatched", function (Blueprint $objTable) {
            $objTable->string("payment_memo")->after("report_revenue_usd")->nullable()->index("idx_payment-memo");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("soundblock_payments_music_projects", function (Blueprint $objTable) {
            $objTable->dropColumn("payment_memo");
        });
        Schema::table("soundblock_payments_music_users", function (Blueprint $objTable) {
            $objTable->dropColumn("payment_memo");
        });
        Schema::table("soundblock_reports_music_matched", function (Blueprint $objTable) {
            $objTable->dropColumn("payment_memo");
        });
        Schema::table("soundblock_reports_music_unmatched", function (Blueprint $objTable) {
            $objTable->dropColumn("payment_memo");
        });
    }
}
