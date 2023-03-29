<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSoundblockDataPlatformsReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("soundblock_data_platforms_reports", function (Blueprint $objTable) {
            $objTable->bigIncrements("data_id")->index("idx_data-id")->unique("uidx_data-id");
            $objTable->uuid("data_uuid")->index("idx_data-id")->unique("uidx_data-uuid");

            $objTable->unsignedBigInteger("platform_id")->index("idx_platform-id");
            $objTable->uuid("platform_uuid")->index("idx_platform-uuid");

            $objTable->string("report_year", 4);
            $objTable->string("report_month", 2);

            $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED)->index(BaseModel::IDX_STAMP_CREATED)->nullable();
            $objTable->timestamp(BaseModel::CREATED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED_BY)->nullable();

            $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED)->index(BaseModel::IDX_STAMP_UPDATED)->nullable();
            $objTable->timestamp(BaseModel::UPDATED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED_BY)->nullable();

            $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED)->index(BaseModel::IDX_STAMP_DELETED)->nullable();
            $objTable->timestamp(BaseModel::DELETED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED_BY)->nullable();

            $objTable->index(["platform_id", "stamp_deleted_at"], "idx_platform-id_stamp-deleted-at");
            $objTable->index(["platform_uuid", "stamp_deleted_at"], "idx_platform-uuid_stamp-deleted-at");

            $objTable->index(["report_year", "stamp_deleted_at"], "idx_report-year_stamp-deleted-at");
            $objTable->index(["report_year", "report_month", "stamp_deleted_at"], "idx_report-year_report-month_stamp-deleted-at");
            $objTable->index(["platform_id", "report_year", "report_month", "stamp_deleted_at"], "idx_platform-id_report-year_report-month_stamp-deleted-at");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("soundblock_data_platforms_reports");
    }
}
