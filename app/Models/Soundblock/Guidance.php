<?php
namespace App\Models\Soundblock;

use App\Models\BaseModel;
use App\Models\Casts\StampCast;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guidance extends BaseModel {

    protected $table = "soundblock_guidance";

    protected $primaryKey = "guide_id";

    protected string $uuid = "guide_uuid";

    protected $guarded = ["flag_active"];

    protected $casts = [
        BaseModel::STAMP_CREATED_BY => StampCast::class,
        BaseModel::STAMP_UPDATED_BY => StampCast::class,
        "flag_active" => "boolean"
    ];

    protected $hidden = [BaseModel::CREATED_AT, BaseModel::UPDATED_AT,
    BaseModel::DELETED_AT,
    BaseModel::STAMP_DELETED,
    BaseModel::STAMP_DELETED_BY,
    "guide_id",
    "guide_uuid",
    ];

    public function feedbacks() : HasMany {
        return $this->HasMany(GuidanceFeedback::class,"guide_id","guide_id");
    }

}
