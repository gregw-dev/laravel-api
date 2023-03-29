<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSoundblockDataExchangeRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("soundblock_data_exchange_rates", function (Blueprint $objTable) {
            $objTable->dropUnique("uidx_data-code");
            $objTable->dropIndex("uidx_data-currency");
            $objTable->dropColumn("data_code");
            $objTable->dropColumn("data_currency");
            $objTable->dropColumn("data_rate");

            $objTable->date("exchange_date")->after("data_uuid");
            $objTable->float("usd_to_aud", 16, 4)->after("exchange_date");
            $objTable->float("usd_to_eur", 16, 4)->after("usd_to_aud");
            $objTable->float("usd_to_gbp", 16, 4)->after("usd_to_eur");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("soundblock_data_exchange_rates", function (Blueprint $objTable) {
            $objTable->dropColumn("exchange_date");
            $objTable->dropColumn("usd_to_aud");
            $objTable->dropColumn("usd_to_eur");
            $objTable->dropColumn("usd_to_gbp");

            $objTable->string("data_code");
            $objTable->string("data_currency");
            $objTable->float("data_rate", 16,4);
            $objTable->unique("data_code", "uidx_data-code");
            $objTable->index("data_currency", "uidx_data-currency");
        });
    }
}
