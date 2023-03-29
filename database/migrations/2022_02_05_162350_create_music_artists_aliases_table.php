<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMusicArtistsAliasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('music_artists_aliases', function (Blueprint $objTable) {
            $objTable->bigIncrements('row_id')->index("uidx_row-id");
            $objTable->uuid("row_uuid")->unique("uidx_row-uuid");

            $objTable->foreignId("artist_id")->constrained("music_artists","artist_id");
            $objTable->foreignUuid("artist_uuid")->constrained("music_artists","artist_uuid");

            $objTable->string("artist_alias")->index("idx_artist-alias");

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

            $objTable->index(["artist_id",BaseModel::DELETED_AT],"idx_artist-id_stamp-deleted-at");
            $objTable->index(["artist_uuid",BaseModel::DELETED_AT],"idx_artist-uuid_stamp-deleted-at");
            $objTable->index(["row_id","artist_id"],"idx_row-id_artist-id");
            $objTable->index(["row_id","artist_id",BaseModel::DELETED_AT],"idx_row-id_artist-id_stamp-deleted-at");
            $objTable->index(["row_uuid","artist_uuid"],"idx_row-uuid_artist-uuid");
            $objTable->index(["row_uuid","artist_uuid",BaseModel::DELETED_AT],"idx_row-uuid_artist-uuid_stamp-deleted-at");
            $objTable->index(["row_uuid",BaseModel::DELETED_AT],"idx_row-uuid_stamp-deleted-at");
            $objTable->unique(["artist_id","artist_alias","stamp_source"],"uidx_artist-id_artist-alias_stamp-source");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('music_artists_aliases');
    }
}
