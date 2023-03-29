<?php

namespace App\Models\Core;

use App\Models\BaseModel;

class CorrespondenceAttachment extends BaseModel
{
    const UUID = "attachment_uuid";

    protected $primaryKey = "attachment_id";

    protected $table = "core_correspondence_attachments";

    protected $guarded = [];

    protected string $uuid = "attachment_uuid";

    protected $hidden = [
        "attachment_id", "correspondence_id", "correspondence_uuid", BaseModel::DELETED_AT,  BaseModel::STAMP_DELETED,
        BaseModel::STAMP_DELETED_BY, BaseModel::CREATED_AT, BaseModel::UPDATED_AT,
         BaseModel::STAMP_CREATED_BY, BaseModel::STAMP_UPDATED_BY,BaseModel::STAMP_CREATED,BaseModel::STAMP_UPDATED
    ];

    public function correspondence(){
        return $this->belongsTo(Correspondence::class);
    }
}
