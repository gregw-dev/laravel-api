<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSoundblockGuidanceFeedbackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('soundblock_guidance_feedback', function (Blueprint $objTable) {
        $objTable->bigIncrements("feedback_id")->index("uidx_feedback-id");
        $objTable->uuid("feedback_uuid")->index("uidx_feedback-uuid");

        $objTable->unsignedBigInteger("parent_id")->nullable()->index("idx_parent-id");
        $objTable->uuid("parent_uuid")->nullable()->index("idx_parent-uuid");

        $objTable->unsignedBigInteger("guide_id")->index("idx_guide-id");
        $objTable->uuid("guide_uuid")->index("idx_guide-uuid");


        $objTable->unsignedBigInteger("user_id")->index("idx_user-id");
        $objTable->uuid("user_uuid")->index("idx_user-uuid");

        $objTable->longText("user_feedback");
        $objTable->string("remote_addr",15);
        $objTable->string("remote_host",255);
        $objTable->string("remote_agent",255);

        $objTable->boolean("flag_approved")->default(false);
        $objTable->boolean("flag_public")->default(false);

        $objTable->index(["feedback_id","parent_id",],"idx_feedback-id_parent-id");
        $objTable->index(["feedback_uuid","parent_uuid",],"idx_feedback-uuid_parent-uuid");
        $objTable->index(["parent_id","feedback_id"],"idx_parent-id_feedback-id");
        $objTable->index(["parent_uuid","feedback_uuid"],"idx_parent-uuid_feedback-uuid");

        $objTable->index(["guide_id","user_id"],"idx_guide-id_user-id");
        $objTable->index(["guide_uuid","user_uuid"],"idx_guide-uuid_user-uuid");
        $objTable->index(["user_id", "guide_id"],"idx_user-id_guide-id");
        $objTable->index(["user_uuid","guide_uuid"],"idx_user-uuid_guide-uuid");

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
        Schema::dropIfExists('soundblock_guidance_feedback');
    }
}
