<?php

namespace App\Repositories\Music\Projects;

use App\Models\Music\Project\ProjectTrackGenre as ProjectTrackGenreModel;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;

class ProjectTrackGenre extends BaseRepository{

    /**@var Model $model */
    protected Model $model;
    public function __construct(ProjectTrackGenreModel $model){
        $this->model = $model;
    }

}
