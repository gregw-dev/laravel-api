<?php

namespace App\Repositories\Soundblock;

use App\Models\BaseModel;
use App\Repositories\BaseRepository;
use App\Models\Soundblock\Tracks\Track as TrackModel;
use App\Models\Soundblock\Tracks\TrackHistory as TrackHistoryModel;

class TrackHistory extends BaseRepository {
    /**
     * UpcCodes constructor.
     * @param TrackHistoryModel $model
     */
    public function __construct(TrackHistoryModel $model) {
        $this->model = $model;
    }

    public function findLatestChange(string $track_uuid){
        return ($this->model->where("track_uuid", $track_uuid)->orderBy(BaseModel::STAMP_CREATED, "desc")->first());
    }

    public function createRecordWithBlockchain(TrackModel $objTrack, string $fieldName, string $oldVal, string $newVal){
        $this->create([
            "track_id" => $objTrack->track_id,
            "track_uuid" => $objTrack->track_uuid,
            "field_name" => $fieldName,
            "old_value" => $oldVal,
            "new_value" => $newVal,
        ]);

        return ([
            ucfirst(str_replace("_", " ", $fieldName)) => [
                "Previous value" => $oldVal,
                "Changed to" => $newVal
            ]
        ]);
    }
}
