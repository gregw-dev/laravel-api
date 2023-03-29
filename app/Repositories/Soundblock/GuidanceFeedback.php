<?php
namespace App\Repositories\Soundblock;

use App\Repositories\BaseRepository;
use App\Models\Soundblock\GuidanceFeedback as guidanceFeedbackModel;
use Illuminate\Database\Eloquent\Model;

class GuidanceFeedback extends BaseRepository {

    protected Model $model;
    /**
     * @param guidanceFeedbackModel $guidanceFeedbackModel
     */
    public function __construct(guidanceFeedbackModel $guidanceFeedbackModel){
    $this->model = $guidanceFeedbackModel;
    }

    public function checkDuplicate($objUser,$objGuidance,$strUserFeedback){
    return $this->model->where([
        "user_uuid" => $objUser->user_uuid,
        "guide_uuid" => $objGuidance->guide_uuid,
        "user_feedback" => $strUserFeedback
    ])->exists();
    }

}
