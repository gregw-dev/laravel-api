<?php

namespace App\Repositories\Core;

use App\Repositories\BaseRepository;
use App\Models\Core\CorrespondenceLookup as CorrespondenceLookupModel;

class CorrespondenceLookup extends BaseRepository {
    /**
     * @var CorrespondenceLookupModel
     */

    /**
     * CorrespondenceLookupRepository constructor.
     * @param CorrespondenceLookupModel $correspondenceLookup
     */
    public function __construct(CorrespondenceLookupModel $correspondenceLookup){
        $this->model = $correspondenceLookup;
    }
    /**
     * @param String $correspondenceUuid
     * @return ?CorrespondenceLookupModel
     */
    public function findByCorrespondenceUuid(string $correspondenceUuid){
    return $this->model->where("correspondence_uuid",$correspondenceUuid)->get();
    }

    public function findByref($strLookupRef) {
        return $this->model->where("lookup_email_ref",$strLookupRef)->first();
    }


}
