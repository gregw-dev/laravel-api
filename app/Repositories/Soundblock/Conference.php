<?php

namespace App\Repositories\Soundblock;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use App\Models\Soundblock\Conference as ConferenceModel;

class Conference extends BaseRepository {
    /**
     * @var ConferenceModel $model
     */
    protected Model $model;
    /**
     * @param ConferenceModel $objModel
     */
    public function __construct(ConferenceModel $objModel){
    $this->model = $objModel;
    }

    public function findByRoomSid($strRoomSid){
        return $this->model->where("room_sid",$strRoomSid)->first();
    }

    public function getActiveConferenceRooms(){
        return $this->model->whereNull("room_stop")->get();
    }


}
