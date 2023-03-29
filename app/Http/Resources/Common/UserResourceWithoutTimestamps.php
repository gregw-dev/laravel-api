<?php

namespace App\Http\Resources\Common;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResourceWithoutTimestamps extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $res = [
            "user_uuid"             => $this->user_uuid,
            "name"                  => $this->name,
            "avatar"                => $this->avatar,

        ];
        if (isset($this->groupsWithPermissions)) {
            $res["groups_with_permissions"] = $this->groupsWithPermissions;
        }
        return($res);
    }
}
