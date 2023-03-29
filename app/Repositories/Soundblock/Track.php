<?php

namespace App\Repositories\Soundblock;

use App\Models\Soundblock\Tracks\Track as TrackModel;
use App\Repositories\BaseRepository;

class Track extends BaseRepository {
    /**
     * UpcCodes constructor.
     * @param TrackModel $model
     */
    public function __construct(TrackModel $model) {
        $this->model = $model;
    }

    public function getAllWhereArtist(string $uuidArtist){
        return ($this->model->whereHas("artists", function ($query) use ($uuidArtist) {
            $query->where("soundblock_artists.artist_uuid", $uuidArtist);
        }))->get();
    }

    public function getAllWhereContributor(string $uuidContributor)
    {
        return ($this->model->whereHas("contributors", function ($query) use ($uuidContributor) {
            $query->where("soundblock_contributors.contributor_uuid", $uuidContributor);
        }))->get();
    }

    public function findByIsrc(string $strIsrc)
    {
        return $this->model->where('track_isrc', $strIsrc)->limit(1)->first();
    }
}
