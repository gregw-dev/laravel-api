<?php

namespace App\Repositories\Soundblock;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use App\Models\Soundblock\ConferenceParticipant as ConferenceParticipantModel;

class ConferenceParticipant extends BaseRepository {
    /**
     * @var ConferenceParticipantModel $model
     */
    protected Model $model;
    /**
     * @param ConferenceParticipantModel $objModel
     */
    public function __construct(ConferenceParticipantModel $objModel){
    $this->model = $objModel;
    }

    public function findByParticipantSid($participantSid){
    return $this->model->where(['participating_sid' => $participantSid])->first();
    }

    public function getInactiveRoomParticipants($timeDiffAllowed){
      return $this->model->whereNull("room_stop")->where("stamp_updated_at","<=",$timeDiffAllowed)->get();
    }

    public function getConferenceRoomParticipants($strconferenceUuid){
        return $this->model->where("conference_uuid",$strconferenceUuid)->get();
    }

    public function getUserActiveConferenceRoom($strUserUuid){
        return $this->model->where("user_uuid",$strUserUuid)->whereNull("room_stop")->latest()->first();
    }
}
