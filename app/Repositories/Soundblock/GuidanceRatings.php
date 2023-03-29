<?php
namespace App\Repositories\Soundblock;

use App\Repositories\BaseRepository;
use App\Models\Soundblock\Guidanceratings as guidanceratingsModel;
use Illuminate\Database\Eloquent\Model;

class Guidanceratings extends BaseRepository {

    protected Model $model;
    /**
     * @param guidanceratingsModel $guidanceratingsModel
     */
    public function __construct(guidanceratingsModel $guidanceratingsModel){
    $this->model = $guidanceratingsModel;
    }

    public function findByUser($objUser,$ObjGuide) {
    return $this->model->where("user_uuid",$objUser->user_uuid)->where("guide_uuid", $ObjGuide->guide_uuid)->first();
    }

    public function findUserRatings($objUser) {
        return $this->model->where("user_id",$objUser->user_id)->get();
    }

}
