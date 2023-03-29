<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMusicProjectsTracksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('music_projects_tracks', function (Blueprint $objTable) {
            $objTable->bigIncrements('track_id')->index("idx_track-id")->unique("uidx_track-id");
            $objTable->uuid('track_uuid')->index("idx_track-uuid")->unique("uidx_track-uuid");
            $objTable->foreignId("project_id")->constrained("music_projects","project_id");
            $objTable->foreignUuid("project_uuid")->constrained("music_projects","project_uuid");

            $objTable->unsignedTinyInteger("disc_number")->index("idx_disc-number");
            $objTable->unsignedTinyInteger("track_number")->index("idx_track-number");
            $objTable->string("track_name")->index("idx_track-name");
            $objTable->time("track_duration");

            $objTable->unsignedBigInteger("stamp_epoch")->index("idx_stamp-epoch");
            $objTable->date("stamp_date")->index("idx_stamp-date");
            $objTable->time("stamp_time")->index("idx_stamp-time");

            $objTable->string("stamp_source")->index("idx-stamp-source");

            $objTable->string("url_allmusic")->index("idx_url-allmusic");
            $objTable->string("url_amazon")->index("idx_url-amazon");
            $objTable->string("url_spotify")->index("idx_url-spotify");

            $objTable->enum("flag_allmusic",["N","Y",""])->default("N")->index("idx_flag-allmusic");

            $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED)->index(BaseModel::IDX_STAMP_CREATED)->nullable();
            $objTable->timestamp(BaseModel::CREATED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED_BY)->nullable();

            $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED)->index(BaseModel::IDX_STAMP_UPDATED)->nullable();
            $objTable->timestamp(BaseModel::UPDATED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED_BY)->nullable();

            $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED)->index(BaseModel::IDX_STAMP_DELETED)->nullable();
            $objTable->timestamp(BaseModel::DELETED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED_BY)->nullable();

            $objTable->unique(["project_id","disc_number","track_number"],"uidx_project-id_disc-number_track-number");
            $objTable->unique(["project_uuid","disc_number","track_number"],"uidx_project-uuid_disc-number_track-number");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('music_projects_tracks');
    }
}
