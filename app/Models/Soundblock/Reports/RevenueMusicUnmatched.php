<?php

namespace App\Models\Soundblock\Reports;

use App\Models\BaseModel;
use App\Models\Soundblock\Platform;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RevenueMusicUnmatched extends BaseModel
{
    use HasFactory;

    protected $table = "soundblock_reports_music_unmatched";

    protected $primaryKey = "row_id";

    protected string $uuid = "row_uuid";

    protected $hidden = [
        "row_id", "platform_id", BaseModel::DELETED_AT, BaseModel::STAMP_DELETED, BaseModel::STAMP_DELETED_BY,
        BaseModel::UPDATED_AT, BaseModel::STAMP_UPDATED_BY, BaseModel::CREATED_AT, BaseModel::STAMP_CREATED_BY,
    ];

    public function platform(){
        return ($this->hasOne(Platform::class, "platform_id", "platform_id"));
    }

    public function subPlatform(){
        return ($this->hasOne(Platform::class, "platform_id", "sub_platform_id"));
    }
}
