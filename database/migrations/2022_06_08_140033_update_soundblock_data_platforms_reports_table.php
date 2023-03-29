<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSoundblockDataPlatformsReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("soundblock_data_platforms_reports", function (Blueprint $objTable) {
            $objTable->boolean("flag_notapplicable")->index("idx_flag-notapplicable")->after("report_month")->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("soundblock_data_platforms_reports", function (Blueprint $objTable) {
            $objTable->dropColumn("flag_notapplicable");
            $objTable->dropIndex("idx_flag-notapplicable");
        });
    }
}
