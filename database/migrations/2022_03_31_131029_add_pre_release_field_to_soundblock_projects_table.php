<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPreReleaseFieldToSoundblockProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("soundblock_projects", function (Blueprint $objTable) {
            $objTable->date("date_release_pre")->after("project_date")->nullable()->index("idx_date-release-pre");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("soundblock_projects", function (Blueprint $objTable) {
            $objTable->dropColumn("date_release_pre");
            $objTable->dropIndex("idx_date-release-pre");
        });
    }
}
