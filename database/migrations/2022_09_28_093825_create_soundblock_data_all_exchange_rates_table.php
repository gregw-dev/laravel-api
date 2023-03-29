<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSoundblockDataAllExchangeRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("soundblock_data_all_exchange_rates", function (Blueprint $objTable) {
            $objTable->bigIncrements("data_id")->unique("uidx_data-id");
            $objTable->uuid("data_uuid")->unique("uidx_data-uuid");

            $objTable->date("data_exchange_date")->index("idx_exchange-date");
            $objTable->string("data_currency_code")->index("idx_data-currency-code");
            $objTable->float("data_rate", 16, 4)->index("idx_data-rate");

            $objTable->index(["data_exchange_date", "data_currency_code"], "uidx_date_code");
            $objTable->unique(["data_exchange_date", "data_currency_code", "stamp_deleted_at"], "uidx_date_code_deleted");

            $objTable->index(["data_exchange_date", "data_currency_code", "data_rate"], "uidx_date_code_rate");
            $objTable->index(["data_exchange_date", "data_currency_code", "data_rate", "stamp_deleted_at"], "uidx_date_code_rate_deleted");

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
        Schema::dropIfExists("soundblock_data_all_exchange_rates");
    }
}
