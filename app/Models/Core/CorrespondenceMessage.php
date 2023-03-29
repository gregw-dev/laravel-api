<?php

namespace App\Models\Core;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CorrespondenceMessage extends BaseModel
{
    use HasFactory;

    const UUID = "message_uuid";

    protected $primaryKey = "message_id";

    protected $table = "core_correspondence_messages";

    protected $guarded = [];

    protected string $uuid = "message_uuid";

    protected $appends = ["attachments", "user"];

    protected $hidden = [
        "message_id", "user_id","remote_host", "remote_addr", "remote_agent", "correspondence_id", "correspondence_uuid", "email_id", "email_parent_id", "email_html", BaseModel::DELETED_AT,  BaseModel::STAMP_DELETED,
        BaseModel::STAMP_DELETED_BY, BaseModel::CREATED_AT, BaseModel::UPDATED_AT, BaseModel::STAMP_CREATED_BY, BaseModel::STAMP_UPDATED_BY,BaseModel::STAMP_UPDATED


    ];

    protected $casts = [
        "email_json" => "array"
    ];

        public function getAttachmentsAttribute()  {
        return $this->hasMany(CorrespondenceAttachment::class, "message_id", "message_id")->select(["message_id","attachment_url"])->pluck("attachment_url");

    }

    public function files(){
        return $this->hasMany(CorrespondenceAttachment::class, "message_id", "message_id");
    }

    public function correspondence() : HasOne {
        return $this->HasOne(Correspondence::class, "correspondence_id","correspondence_id");
    }

    public function getUserEmailAttribute($value)
    {
        return  is_null($this->user_id) ? $value : null;
    }

    public function getUserAttribute()
    {
        if (is_null($this->user_id)) {
            return [
                "email" => $this->user_email,
                "name" => null,
                "avatar" => "https://cloud.develop.account.arena.com/assets/static/avatar_v2.jpg"
            ];
        } else {
            $objUser = User::find($this->user_id);
            if ($objUser) {
                $strEmail = is_null($objUser->primary_email) ? null : $objUser->primary_email->user_auth_email;
                return [
                    "email" => $strEmail,
                    "name" => $objUser->name,
                    "avatar" => $objUser->avatar
                ];
            } else {
                return [
                    "email" => null,
                    "name" => null,
                    "avatar" => null
                ];
            }
        }
    }

}
