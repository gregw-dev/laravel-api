<?php

namespace App\Repositories\Core;

use App\Repositories\BaseRepository;
use App\Models\Core\CorrespondenceGroup;

class CorrespondenceGroups extends BaseRepository {
    /**
     * @var CorrespondenceGroup
     */

    /**
     * CorrespondenceGroupRepository constructor.
     * @param CorrespondenceGroup $correspondenceGroup
     */
    public function __construct(CorrespondenceGroup $correspondenceGroup){
        $this->model = $correspondenceGroup;
    }
    /**
     * @param String $correspondenceUuid
     * @return ?CorrespondenceGroup
     */
    public function findByCorrespondenceUuid(string $correspondenceUuid){
    return $this->model->where("correspondence_uuid",$correspondenceUuid)->get();
    }

    public function findByCorrespondenceAndGroupUuid(String $correspondenceUuid, String $groupUuid){
        return $this->model->where("correspondence_uuid",$correspondenceUuid)->where("group_uuid",$groupUuid)->withTrashed()->first();
    }


}
