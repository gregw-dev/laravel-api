<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeTrackVocalColumnsNames extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("soundblock_tracks", function (Blueprint $objTable) {
            $objTable->renameColumn("track_language_id", "track_language_metadata_id");
            $objTable->renameColumn("track_language_uuid", "track_language_metadata_uuid");
            $objTable->renameColumn("track_language_vocals_id", "track_language_audio_id");
            $objTable->renameColumn("track_language_vocals_uuid", "track_language_audio_uuid");
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
            $objTable->renameColumn("track_language_metadata_id", "track_language_id");
            $objTable->renameColumn("track_language_metadata_uuid", "track_language_uuid");
            $objTable->renameColumn("track_language_audio_id", "track_language_vocals_id");
            $objTable->renameColumn("track_language_audio_uuid", "track_language_vocals_uuid");
        });
    }
}
