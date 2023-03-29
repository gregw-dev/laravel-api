<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

class CorrespondenceEmail extends BaseModel
{
    use HasFactory;

    const UUID = "row_uuid";

    protected $primaryKey = "row_id";

    protected $table = "core_correspondence_emails";

    protected $guarded = [];

    protected string $uuid = "row_uuid";

    protected $hidden = [
        "message_id", "correspondence_id", "correspondence_uuid", BaseModel::DELETED_AT,  BaseModel::STAMP_DELETED,
        BaseModel::STAMP_DELETED_BY, BaseModel::CREATED_AT, BaseModel::UPDATED_AT,
    ];

}
