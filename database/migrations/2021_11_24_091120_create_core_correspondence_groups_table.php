<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoreCorrespondenceGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("core_correspondence_groups", function (Blueprint $objTable) {
        $objTable->bigIncrements("row_id")->unique("uidx_row-id");
        $objTable->uuid("row_uuid")->unique("uidx_row-uuid");

        $objTable->unsignedBigInteger("correspondence_id")->index("idx_correspondence-id");
        $objTable->uuid("correspondence_uuid")->index("idx_correspondence-uuid");

        $objTable->unsignedBigInteger("group_id")->index("idx_group-id");
        $objTable->uuid("group_uuid")->index("idx_group-uuid");

        $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED)->nullable()->index(BaseModel::STAMP_CREATED);
        $objTable->timestamp(BaseModel::CREATED_AT)->nullable();
        $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED_BY)->nullable();

        $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED)->nullable()->index(BaseModel::STAMP_UPDATED);
        $objTable->timestamp(BaseModel::UPDATED_AT)->nullable();
        $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED_BY)->nullable();

        $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED)->nullable()->index(BaseModel::STAMP_DELETED);
        $objTable->timestamp(BaseModel::DELETED_AT)->nullable();
        $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED_BY)->nullable();

        $objTable->index(["correspondence_id", "group_id"],"idx_correspondence-id_group-id");
        $objTable->index(["correspondence_uuid", "group_uuid"],"idx_correspondence-uuid_group-uuid");
        $objTable->index([ "group_id","correspondence_id"],"idx_group-id_correspondence-id");
        $objTable->index(["group_uuid","correspondence_uuid"],"idx_group-uuid_correspondence-uuid");



        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("core_correspondence_groups");
    }
}
