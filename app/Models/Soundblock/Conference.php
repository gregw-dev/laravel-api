<?php

namespace App\Models\Soundblock;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Conference extends BaseModel
{
    use HasFactory;

    protected $table = "soundblock_conferences";

    protected $primaryKey = "conference_id";

    protected string $uuid = "conference_uuid";

    protected $guarded = [];

    protected $hidden = [
        "conference_id", BaseModel::CREATED_AT, BaseModel::UPDATED_AT,
        BaseModel::DELETED_AT, BaseModel::STAMP_DELETED, BaseModel::STAMP_DELETED_BY,
    ];
}
