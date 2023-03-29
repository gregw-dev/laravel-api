<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSoundblockContributorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("soundblock_contributors", function (Blueprint $objTable) {
            $objTable->bigIncrements("contributor_id")->unique("uidx_contributor-id");
            $objTable->uuid("contributor_uuid")->unique("uidx_contributor-uuid");

            $objTable->unsignedBigInteger("account_id")->index("idx_account-id");
            $objTable->uuid("account_uuid")->index("idx_account-uuid");

            $objTable->string("contributor_name")->index("idx_contributor-name");

            $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED)->index(BaseModel::IDX_STAMP_CREATED)->nullable();
            $objTable->timestamp(BaseModel::CREATED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED_BY)->nullable();

            $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED)->index(BaseModel::IDX_STAMP_UPDATED)->nullable();
            $objTable->timestamp(BaseModel::UPDATED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED_BY)->nullable();

            $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED)->index(BaseModel::IDX_STAMP_DELETED)->nullable();
            $objTable->timestamp(BaseModel::DELETED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED_BY)->nullable();

            $objTable->unique(["account_id", "contributor_name", "stamp_deleted_at"], "uidx_account-id_contributor-name_stamp-deleted-at");
            $objTable->unique(["account_uuid", "contributor_name", "stamp_deleted_at"], "uidx_account-uuid_contributor-name_stamp-deleted-at");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("soundblock_contributors");
    }
}
