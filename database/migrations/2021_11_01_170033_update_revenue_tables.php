<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRevenueTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("soundblock_reports_projects", function (Blueprint $objTable) {
            $objTable->float("report_revenue_usd", 18, 10)->after("report_currency")->index("idx_report-revenue-usd");
        });
        Schema::table("soundblock_reports_projects_users", function (Blueprint $objTable) {
            $objTable->float("report_revenue_usd", 18, 10)->after("report_currency")->index("idx_report-revenue-usd");
        });
        Schema::rename("soundblock_reports_projects", "soundblock_reports_music_revenue");
        Schema::rename("soundblock_reports_projects_users", "soundblock_reports_music_revenue_users");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("soundblock_reports_music_revenue", function (Blueprint $objTable) {
            $objTable->dropColumn("report_revenue_usd");
            $objTable->dropIndex("idx_report-revenue-usd");
        });
        Schema::table("soundblock_reports_music_revenue_users", function (Blueprint $objTable) {
            $objTable->dropColumn("report_revenue_usd");
            $objTable->dropIndex("idx_report-revenue-usd");
        });
        Schema::rename("soundblock_reports_music_revenue", "soundblock_reports_projects");
        Schema::rename("soundblock_reports_music_revenue_users", "soundblock_reports_projects_users");
    }
}
