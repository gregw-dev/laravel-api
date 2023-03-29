<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSecondaryGenreInSoundblockTracksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("soundblock_tracks", function (Blueprint $objTable) {
            $objTable->unsignedBigInteger("genre_secondary_id")->nullable()->change();
            $objTable->uuid("genre_secondary_uuid")->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("soundblock_tracks", function (Blueprint $objTable) {
            $objTable->unsignedBigInteger("genre_secondary_id")->change();
            $objTable->uuid("genre_secondary_uuid")->change();
        });
    }
}
