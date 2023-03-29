<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSoundblockGuidanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('soundblock_guidance', function (Blueprint $objTable) {
        $objTable->bigIncrements("guide_id")->index("uidx_guide-id");
        $objTable->uuid("guide_uuid")->index("uidx_guide-uuid");

        $objTable->string("guide_ref",255)->unique();
        $objTable->string("guide_title",255);
        $objTable->longText("guide_html");
        $objTable->float("guide_rating",3,1);
        $objTable->boolean("flag_active")->default(false);

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
        Schema::dropIfExists('soundblock_guidance');
    }
}
