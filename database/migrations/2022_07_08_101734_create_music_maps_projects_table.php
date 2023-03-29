<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMusicMapsProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("music_maps_projects", function (Blueprint $objTable) {
            $objTable->bigIncrements("row_id")->unique("uidx_row-id");
            $objTable->uuid("row_uuid")->unique("uidx_row-uuid");

            $objTable->unsignedBigInteger("artist_id")->index("idx_artist-id");
            $objTable->uuid("artist_uuid")->index("idx_artist-uuid");

            $objTable->string("artist_name")->index("idx_artist-name");
            $objTable->string("artist_slug")->index("idx_artist-slug");

            $objTable->unsignedBigInteger("project_id")->index("idx_project-id");
            $objTable->uuid("project_uuid")->index("idx_project-uuid");

            $objTable->string("project_name")->index("idx_project-name");
            $objTable->string("project_slug")->index("idx_project-slug");

            $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED)->index(BaseModel::IDX_STAMP_CREATED)->nullable();
            $objTable->timestamp(BaseModel::CREATED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED_BY)->nullable();

            $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED)->index(BaseModel::IDX_STAMP_UPDATED)->nullable();
            $objTable->timestamp(BaseModel::UPDATED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED_BY)->nullable();

            $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED)->index(BaseModel::IDX_STAMP_DELETED)->nullable();
            $objTable->timestamp(BaseModel::DELETED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED_BY)->nullable();

            $objTable->index(["artist_uuid", "project_uuid"], "idx_artist-uuid_project-uuid");
            $objTable->index(["artist_name", "project_name"], "idx_artist-name_project-name");
            $objTable->index(["artist_slug", "project_slug"], "idx_artist-slug_project-slug");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("music_maps_projects");
    }
}
