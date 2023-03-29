<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSoundblockPaymentsMusicAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("soundblock_payments_music_accounts", function (Blueprint $objTable) {
            $objTable->bigIncrements('payment_id')->index("idx_payment-id")->unique("uidx_payment-id");
            $objTable->uuid('payment_uuid')->index("idx_payment-uuid")->unique("uidx_payment-uuid");

            $objTable->foreignId("platform_id")->references("platform_id")->on("soundblock_data_platforms");
            $objTable->foreignUuid("platform_uuid")->references("platform_uuid")->on("soundblock_data_platforms");

            $objTable->foreignId("account_id")->references("account_id")->on("soundblock_accounts");
            $objTable->foreignUuid("account_uuid")->references("account_uuid")->on("soundblock_accounts");

            $objTable->float("payment_amount",18,10);
            $objTable->string("payment_memo");

            $objTable->date("date_starts")->index("idx_date-starts");
            $objTable->date("date_ends")->index("idx_date-ends");

            $objTable->unique(["platform_id","account_id","date_starts","date_ends"],"uidx_platform-id_account-id_date-starts_date-ends");
            $objTable->unique(["platform_uuid","account_uuid","date_starts","date_ends"],"uidx_platform-uuid_account-uuid_date-starts_date-ends");

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
        Schema::dropIfExists("soundblock_payments_music_accounts");
    }
}
