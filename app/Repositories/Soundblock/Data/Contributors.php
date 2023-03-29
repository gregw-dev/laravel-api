<?php

namespace App\Repositories\Soundblock\Data;

use App\Models\Soundblock\Data\Contributor;
use App\Repositories\BaseRepository;

class Contributors extends BaseRepository {
    /**
     * ProjectsRoles constructor.
     * @param \App\Models\Soundblock\Data\Contributor $model
     */
    public function __construct(Contributor $model) {
        $this->model = $model;
    }

    public function getNamesByUUids(array $arrUuids){
        return ($this->model->whereIn("data_uuid", $arrUuids)->orderBy("data_contributor", "asc")->pluck("data_contributor"));
    }

    public function getComposerUuid(){
        return ($this->model->where("data_contributor", "Composer")->value("data_uuid"));
    }
}
