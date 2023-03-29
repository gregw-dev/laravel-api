<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUpcColumnToReportsUnmatchedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("soundblock_reports_music_unmatched", function (Blueprint $objTable) {
            $objTable->string("project_upc", 20)->after("track_isrc")->index("idx_project-upc")->nullable();
            $objTable->index(["project_upc", "stamp_deleted_at"], "idx_project-upc_deleted-at");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("soundblock_reports_music_unmatched", function (Blueprint $objTable) {
            $objTable->dropColumn("project_upc");
            $objTable->dropIndex("idx_project-upc");
            $objTable->dropIndex("idx_project-upc_deleted-at");
        });
    }
}
