<?php

namespace App\Repositories\Core;

use App\Repositories\BaseRepository;
use App\Models\Core\CorrespondenceUser;

class CorrespondenceUsers extends BaseRepository {
    /**
     * @var CorrespondenceUser
     */

    /**
     * CorrespondenceUserRepository constructor.
     * @param CorrespondenceUser $correspondenceUser
     */
    public function __construct(CorrespondenceUser $correspondenceUser){
        $this->model = $correspondenceUser;
    }
    /**
     * @param String $correspondenceUuid
     * @return ?CorrespondenceUser
     */
    public function findByCorrespondenceUuid(string $correspondenceUuid){
    return $this->model->where("correspondence_uuid",$correspondenceUuid)->get();
    }

    public function findByCorrespondenceAndUserUuid($strCorrospondenceUuid,$strUserUuid){
        return $this->model->where("correspondence_uuid",$strCorrospondenceUuid)->where("user_uuid",$strUserUuid)->withTrashed()->first();
    }


}
