<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoreCorrespondenceUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("core_correspondence_users", function (Blueprint $objTable) {
        $objTable->bigIncrements("row_id")->unique("uidx_row-id");
        $objTable->string("row_uuid")->unique("uidx_row-uuid");
        $objTable->unsignedBigInteger("correspondence_id")->index("idx_correspondence-id");
        $objTable->uuid("correspondence_uuid")->index("idx_correspondence-uuid");
        $objTable->unsignedBigInteger("user_id")->index("idx_user-id");
        $objTable->uuid("user_uuid")->index("idx_user-uuid");

        $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED)->nullable()->index(BaseModel::STAMP_CREATED);
        $objTable->timestamp(BaseModel::CREATED_AT)->nullable();
        $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED_BY)->nullable();

        $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED)->nullable()->index(BaseModel::STAMP_UPDATED);
        $objTable->timestamp(BaseModel::UPDATED_AT)->nullable();
        $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED_BY)->nullable();

        $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED)->nullable()->index(BaseModel::STAMP_DELETED);
        $objTable->timestamp(BaseModel::DELETED_AT)->nullable();
        $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED_BY)->nullable();

        $objTable->index(["correspondence_id","user_id"],"idx_correspondence-id_user-id");
        $objTable->index(["correspondence_uuid","user_uuid"],"idx_correspondence-uuid_user-uuid");
        $objTable->index(["user_id","correspondence_id"],"idx_user-id_correspondence-id");
        $objTable->index(["user_uuid","correspondence_uuid"],"idx_user-uuid_correspondence-uuid");


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("core_correspondence_users");
    }
}
