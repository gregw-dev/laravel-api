<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\BaseModel;

class CreateMusicProjectTracksStylesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('music_projects_tracks_styles', function (Blueprint $objTable) {
            $objTable->bigIncrements('row_id')->index("idx_row-id")->unique("uidx_row-id");
            $objTable->uuid('row_uuid')->index("idx_row-uuid")->unique("uidx_row-uuid");
            $objTable->foreignId("track_id")->constrained("music_projects_tracks","track_id");
            $objTable->foreignUuid("track_uuid")->constrained("music_projects_tracks","track_uuid");

            $objTable->string("style_id")->index("idx_style-id");
            $objTable->uuid("style_uuid")->index("idx_style-uuid");

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

            $objTable->index(["track_id","style_id"], "idx_track-id_style_id");
            $objTable->index(["style_id", "track_id"], "idx_style-id_track-id");
            $objTable->index(["track_uuid","style_uuid"], "idx_track-uuid_style_uuid");
            $objTable->index(["style_uuid", "track_uuid"], "idx_style-uuid_track-uuid");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('music_projects_tracks_styles');
    }
}
