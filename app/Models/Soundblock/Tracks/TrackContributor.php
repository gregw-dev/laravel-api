<?php

namespace App\Models\Soundblock\Tracks;

use App\Models\BaseModel;
use App\Models\Casts\StampCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrackContributor extends BaseModel
{
    use HasFactory;

    protected $table = "soundblock_tracks_contributors";

    protected $primaryKey = "row_id";

    protected string $uuid = "row_uuid";

    protected $guarded = [];

    protected $hidden = [
        "track_id", "file_id", "row_id", "contributor_id", "contributor_role_id", BaseModel::DELETED_AT, BaseModel::STAMP_DELETED,
        BaseModel::STAMP_DELETED_BY, BaseModel::UPDATED_AT, BaseModel::STAMP_UPDATED_BY, BaseModel::CREATED_AT, BaseModel::STAMP_CREATED_BY,
    ];

    protected $casts = [
        BaseModel::STAMP_CREATED_BY => StampCast::class,
        BaseModel::STAMP_UPDATED_BY => StampCast::class,
    ];

    public function track(){
        return ($this->hasOne(Track::class, "track_id", "track_id"));
    }
}
