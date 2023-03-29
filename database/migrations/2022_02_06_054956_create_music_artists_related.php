<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMusicArtistsRelated extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('music_artists_related', function (Blueprint $objTable) {
            $objTable->bigIncrements('row_id')->index("idx_row-id")->unique("uidx_row-id");
            $objTable->uuid('row_uuid')->index("idx_row-uuid")->unique("uidx_row-uuid");
            $objTable->foreignId("artist_id")->constrained("music_artists","artist_id");
            $objTable->foreignUuid("artist_uuid")->constrained("music_artists","artist_uuid");
            $objTable->unsignedBigInteger("related_id")->index("idx_related-id");
            $objTable->uuid("related_uuid")->index("idx_related-uuid");

            $objTable->unsignedBigInteger("stamp_epoch")->index("idx_stamp-epoch");
            $objTable->date("stamp_date")->index("idx_stamp-date");
            $objTable->time("stamp_time")->index("idx_stamp-time");
            $objTable->string("stamp_source")->index("idx-stamp-source");

            $objTable->enum("flag_influence",["N","Y",""])->default("N")->index("idx_flag-influence");
            $objTable->enum("flag_similarity",["N","Y",""])->default("N")->index("idx_flag-similarity");

            $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED)->index(BaseModel::IDX_STAMP_CREATED)->nullable();
            $objTable->timestamp(BaseModel::CREATED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED_BY)->nullable();

            $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED)->index(BaseModel::IDX_STAMP_UPDATED)->nullable();
            $objTable->timestamp(BaseModel::UPDATED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED_BY)->nullable();

            $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED)->index(BaseModel::IDX_STAMP_DELETED)->nullable();
            $objTable->timestamp(BaseModel::DELETED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED_BY)->nullable();

            $objTable->unique(["artist_id","related_id","stamp_source"],"uidx_artist-id_related-id_stamp-source");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('music_artists_related');
    }
}
