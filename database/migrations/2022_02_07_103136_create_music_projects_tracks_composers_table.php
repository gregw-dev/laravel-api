<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMusicProjectsTracksComposersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('music_projects_tracks_composers', function (Blueprint $objTable) {
            $objTable->bigIncrements('composer_id')->index("idx_composer-id")->unique("uidx_composer-id");
            $objTable->uuid('composer_uuid')->index("idx_composer-uuid")->unique("uidx_composer-uuid");

            $objTable->foreignId("project_id")->constrained("music_projects","project_id");
            $objTable->foreignUuid("project_uuid")->constrained("music_projects","project_uuid");
            $objTable->foreignId("track_id")->constrained("music_projects_tracks","track_id");
            $objTable->foreignUuid("track_uuid")->constrained("music_projects_tracks","track_uuid");
            $objTable->unsignedBigInteger("admin_id")->index("idx_admin-id");
            $objTable->uuid("admin_uuid")->index("idx_admin-uuid");
            $objTable->foreignId("artist_id")->constrained("music_artists","artist_id");
            $objTable->foreignUuid("artist_uuid")->constrained("music_artists","artist_uuid");

            $objTable->unsignedBigInteger("stamp_epoch")->index("idx_stamp-epoch");
            $objTable->date("stamp_date")->index("idx_stamp-date");
            $objTable->time("stamp_time")->index("idx_stamp-time");
            $objTable->string("stamp_source")->index("idx-stamp-source");

            $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED)->index(BaseModel::IDX_STAMP_CREATED)->nullable();
            $objTable->timestamp(BaseModel::CREATED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED_BY)->nullable();

            $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED)->index(BaseModel::IDX_STAMP_UPDATED)->nullable();
            $objTable->timestamp(BaseModel::UPDATED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED_BY)->nullable();

            $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED)->index(BaseModel::IDX_STAMP_DELETED)->nullable();
            $objTable->timestamp(BaseModel::DELETED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED_BY)->nullable();

            $objTable->string("url_allmusic")->index("idx_url-allmusic");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('music_projects_tracks_composers');
    }
}
