<?php

namespace App\Models\Music\Project;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectTrackGenre extends BaseModel
{
    use HasFactory;

    protected $table = "music_projects_tracks_genres";

    protected $hidden = [
        "row_id", "project_id", BaseModel::CREATED_AT, BaseModel::UPDATED_AT,
        BaseModel::DELETED_AT, BaseModel::STAMP_DELETED, BaseModel::STAMP_DELETED_BY,
    ];

    protected $primaryKey = "row_id";

    protected string $uuid = "row_uuid";
}
