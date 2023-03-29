<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSoundblockPaymentsMusicUsersBalanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("soundblock_payments_music_users_balance", function (Blueprint $objTable) {
            $objTable->bigIncrements("row_id")->index("idx_row-id")->unique("uidx_row-id");
            $objTable->uuid("row_uuid")->index("idx_row-uuid")->unique("uidx_row-uuid");

            $objTable->unsignedBigInteger("user_id")->index("idx_user-id");
            $objTable->uuid("user_uuid")->index("idx_user-uuid");

            $objTable->unsignedBigInteger("project_id")->nullable()->index("idx_project-id");
            $objTable->uuid("project_uuid")->nullable()->index("idx_project-uuid");

            $objTable->unsignedBigInteger("platform_id")->nullable()->index("idx_platform-id");
            $objTable->uuid("platform_uuid")->nullable()->index("idx_platform-uuid");

            $objTable->float("user_balance",18,10);
            $objTable->float("payment_amount",18,10);
            $objTable->string("payment_memo");

            $objTable->string("withdrawal_method")->nullable();
            $objTable->string("withdrawal_status")->nullable();
            $objTable->unsignedBigInteger("withdrawal_method_id")->nullable()->index("idx_withdrawal-method-id");
            $objTable->uuid("withdrawal_method_uuid")->nullable()->index("idx_withdrawal-method-uuid");

            $objTable->date("date_starts")->nullable()->index("idx_date-starts");
            $objTable->date("date_ends")->nullable()->index("idx_date-ends");

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
        Schema::dropIfExists("soundblock_payments_music_users_balance");
    }
}
