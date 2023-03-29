<?php

namespace App\Models\Soundblock\Projects\Deployments;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeploymentTakedown extends BaseModel {
    use HasFactory;

    protected $table = "soundblock_projects_deployments_takedowns";

    protected $primaryKey = "takedown_id";

    protected string $uuid = "takedown_uuid";

    protected $guarded = [];

    protected $casts = [
        "flag_status" => "boolean",
    ];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        "deployment_id", "takedown_id", BaseModel::CREATED_AT, BaseModel::UPDATED_AT, BaseModel::DELETED_AT,
        BaseModel::STAMP_DELETED, BaseModel::STAMP_DELETED_BY,
    ];

    public function deployment(){
        return($this->belongsTo(Deployment::class, "deployment_id", "deployment_id"));
    }
}
