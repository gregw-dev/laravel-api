<?php

namespace App\Models\Soundblock\Data;

use App\Models\BaseModel;
use App\Models\Soundblock\Platform;

class PlatformReportMetadata extends BaseModel
{
    protected $table = "soundblock_data_platforms_reports_metadata";

    protected $primaryKey = "data_id";

    protected string $uuid = "data_uuid";

    protected $hidden = [
        "data_id", "platform_id", BaseModel::DELETED_AT, BaseModel::STAMP_DELETED, BaseModel::STAMP_DELETED_BY,
    ];

    public function platform(){
        return ($this->hasOne(Platform::class, "platform_id", "platform_id"));
    }
}
