<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSoundblockDataPlatformsReportsMetadataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("soundblock_data_platforms_reports_metadata", function (Blueprint $objTable) {
            $objTable->bigIncrements("data_id")->unique("uidx_data-id");
            $objTable->uuid("data_uuid")->unique("uidx_data-uuid");

            $objTable->unsignedBigInteger("platform_id")->index("idx_platform-id");
            $objTable->uuid("platform_uuid")->index("idx_platform-uuid");

            $objTable->string("report_type")->index("idx_report-type");
            $objTable->string("report_file_name")->index("idx_report-file-name");

            $objTable->date("date_starts")->nullable()->index("idx_date-starts");
            $objTable->date("date_ends")->nullable()->index("idx_date-ends");

            $objTable->integer("report_quantity_matched")->nullable()->index("idx_report-quantity-matched");
            $objTable->float("report_revenue_usd_matched",18,10)->nullable()->index("idx_report-revenue-usd-matched");
            $objTable->integer("report_quantity_unmatched")->nullable()->index("idx_report-quantity-unmatched");
            $objTable->float("report_revenue_usd_unmatched",18,10)->nullable()->index("idx_report-revenue-usd-unmatched");

            $objTable->string("status")->index("idx_status");

            $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED)->index(BaseModel::IDX_STAMP_CREATED)->nullable();
            $objTable->timestamp(BaseModel::CREATED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED_BY)->nullable();

            $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED)->index(BaseModel::IDX_STAMP_UPDATED)->nullable();
            $objTable->timestamp(BaseModel::UPDATED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED_BY)->nullable();

            $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED)->index(BaseModel::IDX_STAMP_DELETED)->nullable();
            $objTable->timestamp(BaseModel::DELETED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED_BY)->nullable();

            $objTable->index(["status", "stamp_deleted_at"], "idx_status_deleted");

            $objTable->index(["date_ends", "status"], "idx_date_status");
            $objTable->index(["date_ends", "status", "stamp_deleted_at"], "idx_date_status_deleted");

            $objTable->index(["platform_uuid", "date_ends"], "idx_platform_date");
            $objTable->index(["platform_uuid", "date_ends", "status"], "idx_platform_date_status");
            $objTable->index(["platform_uuid", "date_ends", "status", "stamp_deleted_at"], "idx_platform_date_status_deleted");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("soundblock_data_platforms_reports_metadata");
    }
}
