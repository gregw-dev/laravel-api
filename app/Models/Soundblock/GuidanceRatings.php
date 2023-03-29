<?php
namespace App\Models\Soundblock;

use App\Models\BaseModel;

class Guidanceratings extends BaseModel {

    protected $table = "soundblock_guidance_ratings";

    protected $primaryKey = "rating_id";

    protected string $uuid = "rating_uuid";

    protected $guarded = [];

    protected $hidden = [
        "rating_id",
        "guide_id",
        "user_id",
        BaseModel::CREATED_AT,
        BaseModel::UPDATED_AT,
        BaseModel::DELETED_AT,
        BaseModel::STAMP_DELETED,
        BaseModel::STAMP_DELETED_BY,
        BaseModel::STAMP_CREATED_BY,
        BaseModel::STAMP_UPDATED_BY,
        BaseModel::STAMP_CREATED,
        BaseModel::STAMP_UPDATED,
    ];

}
