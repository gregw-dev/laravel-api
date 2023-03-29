<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRatingsToMusicProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("music_projects", function (Blueprint $objTable) {
            $objTable->float("rating_value")->after("project_duration")->default(0)->index("idx_rating-value");
            $objTable->integer("rating_count")->after("rating_value")->default(0)->index("idx_rating-count");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("music_projects", function (Blueprint $objTable) {
            $objTable->dropColumn("rating_value");
            $objTable->dropColumn("rating_count");
        });
    }
}
