<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("soundblock_projects", function (Blueprint $objTable) {
            $objTable->index("stamp_deleted_at", "idx_stamp-deleted-at");
            $objTable->index("stamp_created_at", "idx_stamp-created-at");
            $objTable->index(["stamp_deleted_at", "stamp_created_at"], "idx_stamp-deleted-created");
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
            $objTable->dropIndex("idx_stamp-deleted-at");
            $objTable->dropIndex("idx_stamp-created-at");
            $objTable->dropIndex("idx_stamp-deleted-created");
        });
    }
}
