<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoreCorrespondenceAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("core_correspondence_attachments", function (Blueprint $objTable) {
        $objTable->bigIncrements("attachment_id")->unique("uidx_attachment-id");
        $objTable->uuid("attachment_uuid")->unique("uidx_attachment-uuid");
        $objTable->unsignedBigInteger("correspondence_id")->index("idx_correspondence-id");
        $objTable->uuid("correspondence_uuid")->index("idx_correspondence-uuid");
        $objTable->unsignedBigInteger("message_id")->index("idx_message-id");
        $objTable->uuid("message_uuid")->index("idx_message-uuid");

        $objTable->string("attachment_name");
        $objTable->string("attachment_url");

        $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED)->index(BaseModel::IDX_STAMP_CREATED)->nullable();
        $objTable->timestamp(BaseModel::CREATED_AT)->nullable();
        $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED_BY)->nullable();

        $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED)->index(BaseModel::IDX_STAMP_UPDATED)->nullable();
        $objTable->timestamp(BaseModel::UPDATED_AT)->nullable();
        $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED_BY)->nullable();

        $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED)->index(BaseModel::IDX_STAMP_DELETED)->nullable();
        $objTable->timestamp(BaseModel::DELETED_AT)->nullable();
        $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED_BY)->nullable();

        $objTable->index(["correspondence_id","message_id"],"idx_corresponce-id_message-id");
        $objTable->index(["correspondence_uuid","message_uuid"],"idx_corresponce-uuid_message-uuid");
        $objTable->index(["message_id","correspondence_id"],"idx_message-id_corresponce-id");
        $objTable->index(["message_uuid","correspondence_uuid"],"idx_message-uuid_corresponce-uuid");

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("core_correspondence_attachments");
    }
}
