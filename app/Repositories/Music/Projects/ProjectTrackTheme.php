<?php

namespace App\Repositories\Music\Projects;

use App\Models\Music\Project\ProjectTrackTheme as ProjectTrackThemeModel;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;

class ProjectTrackTheme extends BaseRepository{

    /**@var Model $model */
    protected Model $model;
    public function __construct(ProjectTrackThemeModel $model){
        $this->model = $model;
    }

}
