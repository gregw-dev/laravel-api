<?php

namespace App\Models\Core\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Hasone;
use App\Models\Core\Auth\AuthGroup;
use App\Models\BaseModel;

class AuthPermissionsGroupsUser extends BaseModel
{
    use HasFactory;

    protected $table = "core_auth_permissions_groups_users";

    protected $primaryKey = "row_id";

    protected string $uuid = "row_uuid";

    protected $guarded = [];

    protected $with = ["authGroup"];

    public function authGroup() : Hasone
    {
        return $this->hasOne(AuthGroup::class,"group_uuid", "group_uuid")->select(["group_uuid", "group_name"]);
    }
}
