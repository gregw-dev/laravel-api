<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFlagPermanentFieldToSoundblockContributors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('soundblock_contributors', function (Blueprint $objTable) {
            $objTable->boolean("flag_permanent")->after("contributor_name")->default(false);
            $objTable->index(["flag_permanent", "stamp_deleted_at"], "idx_flag-permanent_stamp-deleted-at");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('soundblock_contributors', function (Blueprint $objTable) {
            $objTable->dropColumn("flag_permanent");
            $objTable->dropIndex("idx_flag-permanent_stamp-deleted-at");
        });
    }
}
