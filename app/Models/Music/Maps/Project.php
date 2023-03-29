<?php

namespace App\Models\Music\Maps;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends BaseModel
{
    use HasFactory;

    protected $table = "music_maps_projects";

    protected $hidden = [
        "row_id", "project_id", "artist_id", BaseModel::CREATED_AT, BaseModel::UPDATED_AT,
        BaseModel::DELETED_AT, BaseModel::STAMP_DELETED, BaseModel::STAMP_DELETED_BY,
    ];
    protected $primaryKey = "row_id";
    protected string $uuid = "row_uuid";
}
