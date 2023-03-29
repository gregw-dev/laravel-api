<?php

namespace App\Models\Support\Ticket;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Support\Ticket\SupportTicket;

class SupportTicketLookup extends BaseModel {
    use SoftDeletes;

    protected $table = "support_tickets_lookup";

    protected $primaryKey = "row_id";

    protected string $uuid = "row_uuid";

    protected $hidden = [
        "row_id", "row_uuid", "ticket_id", "ticket_uuid",
        BaseModel::CREATED_AT, BaseModel::UPDATED_AT,
        BaseModel::DELETED_AT, BaseModel::STAMP_DELETED, BaseModel::STAMP_DELETED_BY,
    ];

    protected $guarded = [];

    protected function ticket() {
    return $this->hasOne(SupportTicket::class, "ticket_id", "ticket_id");
    }
}

