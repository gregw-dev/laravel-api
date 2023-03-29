<?php
namespace App\Models\Soundblock;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GuidanceFeedback extends BaseModel {

    protected $table = "soundblock_guidance_feedback";

    protected $primaryKey = "feedback_id";

    protected string $uuid = "feedback_uuid";

    protected $guarded = [];

    protected $hidden = [
        BaseModel::CREATED_AT,
        BaseModel::UPDATED_AT,
        BaseModel::DELETED_AT,
        BaseModel::STAMP_DELETED,
        BaseModel::STAMP_DELETED_BY,
        BaseModel::STAMP_CREATED_BY,
        BaseModel::STAMP_UPDATED_BY,
        BaseModel::STAMP_CREATED,
        BaseModel::STAMP_UPDATED,
        "guide_id",
        "remote_addr",
        "remote_host",
        "remote_agent",
        "flag_active",
        "feedback_id",
        "user_id",
        "parent_id"
    ];

    public function guidance() : HasOne {
        return $this->HasOne(Guidance::class,"guide_id", "guide_id");
    }

    public function getRepliesAttribute()  {
    return $this->where("parent_uuid",$this->feedback_uuid)->get();
    }
}
