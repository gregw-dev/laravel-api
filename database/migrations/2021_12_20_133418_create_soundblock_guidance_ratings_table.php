<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSoundblockGuidanceRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('soundblock_guidance_ratings', function (Blueprint $objTable) {
        $objTable->bigIncrements("rating_id")->index("uidx_rating-id");
        $objTable->uuid("rating_uuid")->index("uidx_rating-uuid");

        $objTable->unsignedBigInteger("guide_id")->index("uidx_guide-id");
        $objTable->uuid("guide_uuid")->index("uidx_guide-uuid");

        $objTable->unsignedBigInteger("user_id")->index("uidx_user-id");
        $objTable->uuid("user_uuid")->index("uidx_user-uuid");

        $objTable->float("user_rating",2,1);

        $objTable->index(["guide_id","user_id"],"uidx_guide-id_user-id");
        $objTable->index(["guide_uuid","user_uuid"],"uidx_guide-uuid_user-uuid");
        $objTable->index(["user_id","guide_id"],"uidx_user-id_guide-id");
        $objTable->index(["user_uuid","guide_uuid"],"uidx_user-uuid_guide-uuid");

        $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED)->index(BaseModel::IDX_STAMP_CREATED)->nullable();
        $objTable->timestamp(BaseModel::CREATED_AT)->nullable();
        $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED_BY)->nullable();

        $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED)->index(BaseModel::IDX_STAMP_UPDATED)->nullable();
        $objTable->timestamp(BaseModel::UPDATED_AT)->nullable();
        $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED_BY)->nullable();

        $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED)->index(BaseModel::IDX_STAMP_DELETED)->nullable();
        $objTable->timestamp(BaseModel::DELETED_AT)->nullable();
        $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED_BY)->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('soundblock_guidance_ratings');
    }
}
