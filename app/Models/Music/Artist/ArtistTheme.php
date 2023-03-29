<?php

namespace App\Models\Music\Artist;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ArtistTheme extends BaseModel
{
    use HasFactory;

    protected $table = "music_artists_themes";

    protected $hidden = [
        "row_id", "artist_id", BaseModel::CREATED_AT, BaseModel::UPDATED_AT,
        BaseModel::DELETED_AT, BaseModel::STAMP_DELETED, BaseModel::STAMP_DELETED_BY,
        BaseModel::STAMP_CREATED, BaseModel::STAMP_CREATED_BY, BaseModel::STAMP_UPDATED, BaseModel::STAMP_UPDATED_BY,
        "stamp_epoch", "stamp_date", "stamp_time", "stamp_source", "artist_uuid", "pivot"
    ];

    protected $primaryKey = "row_id";

    protected string $uuid = "row_uuid";

    protected $guarded = [];

}
