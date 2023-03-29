<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoreCorrespondenceLookupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('core_correspondence_lookup', function (Blueprint $objTable) {
            $objTable->bigIncrements("row_id")->unique("uidx_row-id");
            $objTable->uuid("row_uuid")->unique("uidx_row-uuid");
            $objTable->unsignedBigInteger("correspondence_id")->index("idx_correspondence-id");
            $objTable->uuid("correspondence_uuid")->index("idx_correspondence-uuid");
            $objTable->string("lookup_email_ref",255);

            $objTable->index(["correspondence_id", "lookup_email_ref"],"idx_correspondence-id_lookup-email-ref");
            $objTable->index(["correspondence_uuid", "lookup_email_ref"],"idx_correspondence-uuid_lookup-email-ref");
            $objTable->index([ "lookup_email_ref","correspondence_id"],"idx_lookup-email-ref_correspondence-id");
            $objTable->index(["lookup_email_ref","correspondence_uuid"],"idx_lookup-email-ref_correspondence-uuid");

            $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED)->nullable()->index(BaseModel::STAMP_CREATED);
            $objTable->timestamp(BaseModel::CREATED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED_BY)->nullable();

            $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED)->nullable()->index(BaseModel::STAMP_UPDATED);
            $objTable->timestamp(BaseModel::UPDATED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED_BY)->nullable();

            $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED)->nullable()->index(BaseModel::STAMP_DELETED);
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
        Schema::dropIfExists('core_correspondence_lookup');
    }
}
