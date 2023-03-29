<?php

namespace App\Models\Core;

use App\Models\Core\Auth\AuthGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CorrespondenceGroup extends BaseModel
{
    use HasFactory;

    const UUID = "row_uuid";

    protected $primaryKey = "row_id";

    protected $table = "core_correspondence_groups";

    protected $guarded = [];

    protected string $uuid = "row_uuid";

    protected $with = ["group"];

    protected $hidden = [
       "correspondence_id", "correspondence_uuid", BaseModel::DELETED_AT,  BaseModel::STAMP_DELETED,
        BaseModel::STAMP_DELETED_BY, BaseModel::CREATED_AT, BaseModel::UPDATED_AT,
    ];

    public function group() : HasOne
    {
        return $this->HasOne(AuthGroup::class, "group_uuid", "group_uuid")->select(["group_uuid", "group_name"]);
    }

}
