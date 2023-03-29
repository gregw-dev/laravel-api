<?php

namespace App\Repositories\Soundblock;

use App\Models\BaseModel;
use App\Repositories\BaseRepository;
use App\Models\Soundblock\Projects\Deployments\DeploymentTakedown as DeploymentTakedownModel;

class DeploymentTakedown extends BaseRepository {
    public function __construct(DeploymentTakedownModel $objTakedown) {
        $this->model = $objTakedown;
    }

    public function getPendingTakedowns(int $perPage){
        return ($this->model->where("flag_status", false)->orderBy(BaseModel::STAMP_CREATED, "desc")->paginate($perPage));
    }
}
