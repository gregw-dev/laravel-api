<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddArtistAvatarRandToSoundblockArtistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('soundblock_artists', function (Blueprint $objTable) {
        $objTable->integer('artist_avatar_rand')->nullable()->after('url_spotify');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('soundblock_artists', function (Blueprint $objTable) {
        $objTable->dropColumn('artist_avatar_rand');
        });
    }
}
