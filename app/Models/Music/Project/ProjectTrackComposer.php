<?php

namespace App\Models\Music\Project;

use App\Models\BaseModel;
use App\Models\Music\Artist\Artist;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectTrackComposer extends BaseModel
{
    use HasFactory;

    protected $table = "music_projects_tracks_composers";

    protected $hidden = [
        "row_id", "project_id", BaseModel::CREATED_AT, BaseModel::UPDATED_AT,
        BaseModel::DELETED_AT, BaseModel::STAMP_DELETED, BaseModel::STAMP_DELETED_BY,
    ];

    protected $primaryKey = "row_id";

    protected string $uuid = "row_uuid";

    public function track()
    {
        return $this->belongsTo(ProjectTrack::class,"track_id","track_id");
    }

    public function project()
    {
        return $this->belongsTo(Project::class,"project_id","project_id");
    }

    public function artist()
    {
        return $this->belongsTo(Artist::class,"artist_id","artist_id");
    }

}
