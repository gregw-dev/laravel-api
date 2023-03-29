<?php

namespace App\Repositories\Soundblock;

use App\Models\Soundblock\Invites;
use App\Repositories\BaseRepository;

class Invite extends BaseRepository {
    /**
     * InviteRepository constructor.
     * @param Invites $invites
     */
    public function __construct(Invites $invites) {
        $this->model = $invites;
    }

    public function getInviteByHash(string $hash) : ?Invites {
        return $this->model->where("invite_hash", $hash)->first();
    }

    public function getInviteByEmail(string $email) : ?Invites {
        return $this->model->whereRaw("lower(invite_email) = (?)", strtolower($email))->first();
    }

    public function getUnusedInvitesByEmail(string $email) {
        return ($this->model->whereRaw("lower(invite_email) = (?)", strtolower($email))->where("flag_used", false)->get());
    }

    public function updateInviteRole(Invites $objInvite, $objRole){
        $objInvite->project_role_id = $objRole->data_id;
        $objInvite->project_role_uuid = $objRole->data_uuid;
        $objInvite->save();

        return ($objInvite);
    }
}
