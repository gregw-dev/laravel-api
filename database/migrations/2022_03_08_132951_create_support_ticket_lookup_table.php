<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\BaseModel;

class CreateSupportTicketLookupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('support_tickets_lookup', function (Blueprint $objTable) {
        $objTable->bigIncrements("row_id");
        $objTable->uuid("row_uuid");
        $objTable->unsignedBigInteger("ticket_id");
        $objTable->uuid("ticket_uuid");
        $objTable->string("lookup_email_ref");

        $objTable->index(["ticket_id", "lookup_email_ref"],"idx_ticket-id_lookup-email-ref");
        $objTable->index(["ticket_uuid", "lookup_email_ref"],"idx_ticket-uuid_lookup-email-ref");
        $objTable->index([ "lookup_email_ref","ticket_id"],"idx_lookup-email-ref_ticket-id");
        $objTable->index(["lookup_email_ref","ticket_uuid"],"idx_lookup-email-ref_ticket-uuid");

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
        Schema::dropIfExists('support_tickets_lookup');
    }
}
