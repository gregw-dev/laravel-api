<?php

namespace App\Models\Soundblock;

use App\Http\Controllers\Core\User;
use App\Models\BaseModel;
use App\Models\Casts\StampCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ConferenceParticipant extends BaseModel
{
    use HasFactory;

    use HasFactory;

    protected $table = "soundblock_conferences_participants";

    protected $primaryKey = "participant_id";

    protected string $uuid = "participant_uuid";

    protected $guarded = [];

    protected bool $ignoreBootEvents = true;

    // protected $with = ["conference"];

    protected $hidden = [
        "participant_id", "conference_id", "conference_uuid", "user_id", BaseModel::CREATED_AT, BaseModel::UPDATED_AT,
        BaseModel::DELETED_AT, BaseModel::STAMP_DELETED, BaseModel::STAMP_DELETED_BY, BaseModel::STAMP_CREATED_BY, BaseModel::STAMP_UPDATED_BY
    ];

    protected $casts = [
        "participant_ping" => "timestamp",
        BaseModel::STAMP_CREATED_BY => StampCast::class,
        BaseModel::STAMP_UPDATED_BY => StampCast::class,
    ];

    public function conference() : HasOne {
        return $this->HasOne(Conference::class,"conference_id", "conference_id")->select(["conference_id","conference_uuid","room_sid","room_name","room_start","room_stop","room_duration"]);
    }

    public function user(){
        return $this->Hasone(User::class,"user_id", "user_id");
    }


}
