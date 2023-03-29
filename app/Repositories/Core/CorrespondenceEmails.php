<?php

namespace App\Repositories\Core;

use App\Repositories\BaseRepository;
use App\Models\Core\CorrespondenceEmail;

class CorrespondenceEmails extends BaseRepository {
    /**
     * @var CorrespondenceEmail
     */

    /**
     * CorrespondenceEmailRepository constructor.
     * @param CorrespondenceEmail $correspondenceEmail
     */
    public function __construct(CorrespondenceEmail $correspondenceEmail){
        $this->model = $correspondenceEmail;
    }
    /**
     * @param String $correspondenceUuid
     * @return ?CorrespondenceEmail
     */
    public function findByCorrespondenceUuid(string $strCorrespondenceUuid){
        return $this->model->where("correspondence_uuid",$strCorrespondenceUuid)->get();
        }

    public function findByCorrespendenceUuidAndEmail(string $strCorrespondenceUuid, string $strEmail){
        return $this->model->where("correspondence_uuid",$strCorrespondenceUuid)->where("email_address",$strEmail)->withTrashed()->first();
    }

}
