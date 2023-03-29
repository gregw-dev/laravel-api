<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSoundblockReportsRevenueUnmatchedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("soundblock_reports_revenue_unmatched", function (Blueprint $objTable) {
            $objTable->bigIncrements("row_id")->unique("uidx_row-id");
            $objTable->uuid("row_uuid")->unique("uidx_row-uuid");

            $objTable->unsignedBigInteger("platform_id")->index("idx_platform-id");
            $objTable->uuid("platform_uuid")->index("idx_platform-uuid");

            $objTable->string("project_name")->index("idx_project-name");
            $objTable->string("artist_name")->index("idx_artist-name");
            $objTable->string("track_name")->index("idx_track-name");
            $objTable->string("track_isrc")->index("idx_track-isrc");

            $objTable->date("date_starts")->index("idx_date-starts");
            $objTable->date("date_ends")->index("idx_date-ends");
            $objTable->integer("report_plays");
            $objTable->float("report_revenue", 18, 10);
            $objTable->float("report_revenue_usd", 18, 10);
            $objTable->string("report_currency");

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
        Schema::dropIfExists("soundblock_reports_revenue_unmatched");
    }
}
