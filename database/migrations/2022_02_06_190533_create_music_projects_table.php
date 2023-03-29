<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMusicProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('music_projects', function (Blueprint $objTable) {
            $objTable->bigIncrements('project_id')->index("idx_project-id")->unique("uidx_project-id");
            $objTable->uuid('project_uuid')->index("idx_project-uuid")->unique("uidx_project-uuid");
            $objTable->foreignId("artist_id")->constrained("music_artists","artist_id");
            $objTable->foreignUuid("artist_uuid")->constrained("music_artists","artist_uuid");

            $objTable->string("project_type")->index("idx_project-type");
            $objTable->date("project_date")->index("idx_project-date");
            $objTable->year("project_year")->index("idx_project-year");
            $objTable->string("project_name")->index("idx_project-name");
            $objTable->string("project_label")->index("idx_project-label");
            $objTable->time("project_duration")->index("idx_project-duration");

            $objTable->unsignedBigInteger("stamp_epoch")->index("idx_stamp-epoch");
            $objTable->date("stamp_date")->index("idx_stamp-date");
            $objTable->time("stamp_time")->index("idx_stamp-time");

            $objTable->string("stamp_source")->index("idx-stamp-source");

            $objTable->string("url_allmusic")->index("idx_url-allmusic");
            $objTable->string("url_amazon")->index("idx_url-amazon");
            $objTable->string("url_itunes")->index("idx_url-itunes");
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

            $objTable->unique(["project_type","project_year","project_name","project_label","url_allmusic"],"uidx_project-type_project-year_project-name_label_url-allmusic");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('music_projects');
    }
}
