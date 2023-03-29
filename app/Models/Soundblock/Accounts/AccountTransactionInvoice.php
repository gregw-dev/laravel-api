<?php

namespace App\Models\Soundblock\Accounts;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountTransactionInvoice extends BaseModel
{
    use HasFactory;

    protected $table = "soundblock_accounts_transactions_invoices";

    protected $primaryKey = "row_id";

    protected string $uuid = "row_uuid";

    protected $guarded = [];

    protected $hidden = [
        "transaction_id", "invoice_id", "row_id",
        BaseModel::DELETED_AT,  BaseModel::STAMP_DELETED, BaseModel::STAMP_DELETED_BY,
    ];
}
