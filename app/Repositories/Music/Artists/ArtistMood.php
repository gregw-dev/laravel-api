<?php

namespace App\Repositories\Music\Artists;

use App\Helpers\Util;
use App\Helpers\Constant;
use App\Repositories\BaseRepository;
use App\Models\Music\Artist\Artist as ArtistModel;
use App\Models\Music\Artist\ArtistMood as ArtistMoodModel;

class ArtistMood extends BaseRepository
{
    /**
     * ArtistMood constructor.
     * @param ArtistMoodModel $model
     */
    public function __construct(ArtistMoodModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param ArtistModel $artist
     * @param array $arrMoods
     * @return ArtistModel
     * @throws \Exception
     */
    public function createMultiple(ArtistModel $objArtist, array $arrMoods)
    {
        foreach ($arrMoods as $mood) {
            $this->addMoodToArtist($objArtist, $mood);
        }
        return $objArtist;
    }

    public function addMoodToArtist(ArtistModel $objArtist, $mood): ArtistMoodModel
    {
        $objTime = now();

        $objArtistMood = $this->findByArtistAndMood($objArtist, $mood);
        if (!$objArtistMood) {

            $objArtistMood = $this->create([
                "artist_id"   => $objArtist->artist_id,
                "artist_uuid" => $objArtist->artist_uuid,
                "artist_mood"   => $mood,
                "stamp_epoch" => time(),
                "stamp_date" => $objTime->toDateString(),
                "stamp_time" => $objTime->format("H:i:s.u"),
                "stamp_source"   => Constant::ARENA_SOURCE,
            ]);
        } else {
            $objArtistMood->restore();
        }

        return $objArtistMood;
    }

    public function deleteMoodFromArtist(String $strIdentity, ?ArtistModel $objArtist = null)
    {
        $objArtistMood = is_null($objArtist) ? $this->model->find($strIdentity) : $this->findByArtistAndMood($objArtist, $strIdentity);
        return !is_null($objArtistMood) ? $objArtistMood->delete() : false;
    }

    public function autoComplete(string $mood)
    {
        return $this->model->whereRaw("lower(artist_mood) like (?)", "%" . Util::lowerLabel($mood) . "%")->get(["artist_mood"])->unique("artist_mood")->pluck("artist_mood");
    }

    public function checkDuplicate(ArtistModel $objArtist, String $mood): Bool
    {
        return $this->model->where([
            "artist_id" => $objArtist->artist_id,
            "artist_mood" => $mood
        ])->exists();
    }

    public function findByArtistAndMood(ArtistModel $objArtist, String $mood)
    {
        return $this->model->where([
            "artist_id" => $objArtist->artist_id,
            "artist_mood" => $mood
        ])->first();
    }
}
