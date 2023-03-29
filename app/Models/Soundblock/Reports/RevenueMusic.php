<?php

namespace App\Models\Soundblock\Reports;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Soundblock\Projects\Project as ProjectModel;
use App\Models\Soundblock\Tracks\Track as TrackModel;

class RevenueMusic extends BaseModel
{
    use HasFactory;

    protected $table = "soundblock_reports_music_matched";

    protected $primaryKey = "row_id";

    protected string $uuid = "row_uuid";

    protected $hidden = [
        "row_id", "project_id", "track_id", "platform_id",
        BaseModel::DELETED_AT, BaseModel::STAMP_DELETED, BaseModel::STAMP_DELETED_BY,
        BaseModel::UPDATED_AT, BaseModel::STAMP_UPDATED_BY, BaseModel::CREATED_AT, BaseModel::STAMP_CREATED_BY,
    ];

    public function project(){
        return ($this->hasOne(ProjectModel::class, "project_id", "project_id"));
    }

    public function track(){
        return ($this->hasOne(TrackModel::class, "track_id", "track_id"));
    }
}
