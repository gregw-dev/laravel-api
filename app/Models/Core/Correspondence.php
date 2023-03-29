<?php

namespace App\Models\Core;

use App\Models\Users\User;
use App\Models\BaseModel;
use App\Models\Casts\StampCast;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Correspondence extends BaseModel
{
    const UUID = "correspondence_uuid";

    protected $primaryKey = "correspondence_id";

    protected $table = "core_correspondence";

    protected $guarded = [];

    protected string $uuid = "correspondence_uuid";

    protected $appends = ["email", "user_uuid", "groups", "users", "emails"];

    protected $hidden = [
        "correspondence_id", "app_id", "email_id", BaseModel::DELETED_AT,  BaseModel::STAMP_DELETED,
        BaseModel::STAMP_DELETED_BY, BaseModel::CREATED_AT,BaseModel::UPDATED_AT
    ];

    protected $casts = [
        "email_json"    => "array",
        "flag_read"     => "boolean",
        "flag_archived" => "boolean",
        "flag_received" => "boolean",
        BaseModel::STAMP_CREATED_BY => StampCast::class,
        BaseModel::STAMP_UPDATED_BY => StampCast::class,
    ];

    public $metaData = [
        "filters" => [
            "read" => [
                "column" => "flag_read"
            ],
            "archived" => [
                "column" => "flag_archived"
            ],
            "received" => [
                "column" => "flag_received"
            ]
        ],
        "search" => [
            "subject" => [
                "column" => "email_subject"
            ],
            "email" => [
                "column" => "email_address"
            ]
        ],
        "sort" => [
            "app" => [
                "relation" => "app",
                "relation_table" => "core_apps",
                "column" => "app_name"
            ],
            "flag_read" => [
                "column" => "flag_read"
            ],
            "flag_archived" => [
                "column" => "flag_archived"
            ],
            "flag_received" => [
                "column" => "flag_received"
            ]
        ],
    ];

    public function app(){
        return $this->belongsTo(App::class, "app_id", "app_id");
    }


    public function responses(){
        return $this->hasMany(CorrespondenceResponse::class, "correspondence_id", "correspondence_id");
    }

    public function messages() :HasMany{
    return $this->hasMany(CorrespondenceMessage::class, "correspondence_id", "correspondence_id");
    }

    public function getEmailAttribute()
    {
        // return $this->messages()->first()->user_email;
        return DB::table("core_correspondence_messages")->select("user_email")->where("correspondence_id",$this->correspondence_id)->first()->user_email;
    }

    public function getuserUuidAttribute()
    {
        return $this->messages()->first()->user_uuid;
    }

    public function getGroupsAttribute()
    {
        $objGroups = $this->HasMany(CorrespondenceGroup::class, "correspondence_id", "correspondence_id")->pluck("group_uuid");
        $arrGroups = [];
        if ($objGroups) {
            foreach ($objGroups as $strGroupUuid) {
                $objGroup = DB::table('core_auth_groups')->where("group_uuid", $strGroupUuid)->select(["group_uuid", "group_name"])->first();
                array_push($arrGroups, $objGroup);
            }
        }


        return $arrGroups;
    }

    public function getUsersAttribute()
    {
        $objUsers = $this->HasMany(CorrespondenceUser::class, "correspondence_uuid", "correspondence_uuid")->pluck("user_id");
        $arrUsers = [];
        if ($objUsers) {
            foreach ($objUsers as $strUserid) {
                $objUser = User::find($strUserid);
                if ($objUser) {
                    array_push($arrUsers, [
                        "user_uuid" => $objUser->user_uuid,
                        "user_name" => $objUser->name,
                        "image_url" => $objUser->avatar,
                    ]);
                }

            }
        }

        return $arrUsers;
    }

    public function getEmailsAttribute() {
        return $this->HasMany(CorrespondenceEmail::class,"correspondence_id","correspondence_id")->pluck("email_address");
    }


}
