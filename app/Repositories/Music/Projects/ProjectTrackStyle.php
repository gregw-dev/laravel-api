<?php

namespace App\Repositories\Music\Projects;

use App\Models\Music\Project\ProjectTrackStyle as ProjectTrackStyleModel;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;

class ProjectTrackStyle extends BaseRepository{

    /**@var Model $model */
    protected Model $model;
    public function __construct(ProjectTrackStyleModel $model){
        $this->model = $model;
    }

}
