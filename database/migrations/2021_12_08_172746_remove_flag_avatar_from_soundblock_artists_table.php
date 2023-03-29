<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveFlagAvatarFromSoundblockArtistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("soundblock_artists", function (Blueprint $objTable) {
            $objTable->dropColumn("flag_avatar");
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
            $objTable->boolean("flag_avatar")->default(0);
        });
    }
}
