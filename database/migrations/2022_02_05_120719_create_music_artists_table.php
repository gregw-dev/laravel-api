<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMusicArtistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('music_artists', function (Blueprint $objTable) {
            $objTable->bigIncrements('artist_id')->index("idx_artist-id")->unique("uidx_artist-id");
            $objTable->uuid("artist_uuid")->index("idx_artist-uuid")->unique("uidx_artist-uuid");
            $objTable->string("arena_id")->index("idx_arena-id");
            $objTable->string("artist_name")->index("idx_arena-name");
            $objTable->string("artist_active")->nullable();
            $objTable->string("artist_born")->nullable();
            $objTable->unsignedBigInteger("stamp_epoch")->index("idx_stamp-epoch");
            $objTable->date("stamp_date")->index("idx_stamp-date");
            $objTable->time("stamp_time")->index("idx_stamp-time");

            $objTable->string("url_allmusic")->index("idx_url-allmusic")->nullable();
            $objTable->string("url_amazon")->index("idx_url-amazon")->nullable();
            $objTable->string("url_itunes")->index("idx_url-itunes")->nullable();
            $objTable->string("url_lastfm")->index("idx_url-lastfm")->nullable();
            $objTable->string("url_spotify")->index("idx_url-spotify")->nullable();
            $objTable->string("url_wikipedia")->index("idx_url-wikipedia")->nullable();

            $objTable->enum("flag_allmusic",["N","Y",""])->default("N")->index("idx_flag-allmusic");
            $objTable->enum("flag_amazon",["N","Y",""])->default("N")->index("idx_flag-amazon");
            $objTable->enum("flag_itunes",["N","Y",""])->default("N")->index("idx_flag-itunes");
            $objTable->enum("flag_lastfm",["N","Y",""])->default("N")->index("idx_flag-lastfm");
            $objTable->enum("flag_spotify",["N","Y",""])->default("N")->index("idx_flag-spotify");
            $objTable->enum("flag_wikipedia",["N","Y",""])->default("N")->index("idx_flag-wikipedia");

            $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED)->index(BaseModel::IDX_STAMP_CREATED)->nullable();
            $objTable->timestamp(BaseModel::CREATED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED_BY)->nullable();

            $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED)->index(BaseModel::IDX_STAMP_UPDATED)->nullable();
            $objTable->timestamp(BaseModel::UPDATED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED_BY)->nullable();

            $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED)->index(BaseModel::IDX_STAMP_DELETED)->nullable();
            $objTable->timestamp(BaseModel::DELETED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED_BY)->nullable();

            $objTable->index(["artist_id",BaseModel::DELETED_AT],"idx_artist-id_stamp-deleted-at");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('music_artists');
    }
}
