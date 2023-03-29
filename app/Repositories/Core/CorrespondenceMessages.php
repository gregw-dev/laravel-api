<?php

namespace App\Repositories\Core;

use App\Repositories\BaseRepository;
use App\Models\Core\CorrespondenceMessage;

class CorrespondenceMessages extends BaseRepository {
    /**
     * @var CorrespondenceMessage
     */

    /**
     * CorrespondenceAttachmentsRepository constructor.
     * @param CorrespondenceMessage $correspondenceResponse
     */
    public function __construct(CorrespondenceMessage $correspondenceMessage){
        $this->model = $correspondenceMessage;
    }
    /**
     * @param String $strCorrespondenceUuid
     * @param Int
     *
     * @return ?CorrespondenceMessage
     */
    public function findByCorrespondenceUuid(String $strCorrespondenceUuid, Int $perPage){
        return $this->model->where("correspondence_uuid", $strCorrespondenceUuid)->orderByDesc("message_id")->paginate($perPage);
    }
}
