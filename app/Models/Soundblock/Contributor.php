<?php

namespace App\Models\Soundblock;

use App\Models\BaseModel;
use App\Models\Soundblock\Accounts\Account;
use App\Models\Soundblock\Tracks\Track;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contributor extends BaseModel {
    use HasFactory;

    protected $table = "soundblock_contributors";

    protected $primaryKey = "contributor_id";

    protected string $uuid = "contributor_uuid";

    protected $guarded = [];

    protected $hidden = [
        "pivot", "contributor_id", "account_id", BaseModel::CREATED_AT, BaseModel::UPDATED_AT,
        BaseModel::DELETED_AT, BaseModel::STAMP_DELETED, BaseModel::STAMP_DELETED_BY,
    ];

    public function account(){
        return ($this->hasOne(Account::class, "account_id", "account_id"));
    }

    public function tracks(){
        return ($this->belongsToMany(Track::class, "soundblock_tracks_contributors", "contributor_id", "track_id", "contributor_id", "track_id"));
    }
}
