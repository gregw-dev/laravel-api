<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFlagAvatarToSoundblockArtistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("soundblock_artists", function (Blueprint $objTable) {
            $objTable->boolean("flag_avatar")->after("flag_permanent")->default(0)->index("idx_flag-avatar");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("soundblock_artists", function (Blueprint $objTable) {
            $objTable->dropColumn("flag_avatar");
            $objTable->dropIndex("idx_flag-avatar");
        });
    }
}
