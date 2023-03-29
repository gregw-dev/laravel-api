<?php

namespace App\Repositories\Music\Projects;

use App\Models\Music\Project\ProjectTrackMood as ProjectTrackMoodModel;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;

class ProjectTrackMood extends BaseRepository{

    /**@var Model $model */
    protected Model $model;
    public function __construct(ProjectTrackMoodModel $model){
        $this->model = $model;
    }

}
