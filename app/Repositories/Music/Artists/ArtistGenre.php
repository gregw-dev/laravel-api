<?php

namespace App\Repositories\Music\Artists;

use App\Helpers\Util;
use App\Helpers\Constant;
use App\Repositories\BaseRepository;
use App\Models\Music\Artist\Artist as ArtistModel;
use App\Models\Music\Artist\ArtistGenre as ArtistGenreModel;

class ArtistGenre extends BaseRepository
{

    /**
     * ArtistGenre constructor.
     * @param ArtistGenreModel $model
     * @param Genre $genreModel
     */
    public function __construct(ArtistGenreModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param ArtistModel $artist
     * @param array $arrData
     * @return ArtistModel
     * @throws \Exception
     */
    public function createMultiple(ArtistModel $objArtist, array $arrData)
    {
        foreach ($arrData as $genre) {
            $this->addGenreToArtist($objArtist, $genre);
        }
        return $objArtist;
    }

    public function addGenreToArtist(ArtistModel $objArtist, String $genre): ArtistGenreModel
    {
        $objTime = now();
        $objArtistGenre  = $this->findByArtistAndGenre($objArtist, $genre);
        if (!$objArtistGenre) {
            $objArtistGenre = $this->create([
                "artist_id"   => $objArtist->artist_id,
                "artist_uuid" => $objArtist->artist_uuid,
                "artist_genre"   => $genre,
                "stamp_epoch" => time(),
                "stamp_date" => $objTime->toDateString(),
                "stamp_time" => $objTime->format("H:i:s.u"),
                "stamp_source"   => Constant::ARENA_SOURCE,
            ]);
        } else {
            $objArtistGenre->restore();
        }
        return $objArtistGenre;
    }

    public function deleteGenreFromArtist(String $strIdentity, ?ArtistModel $objArtist = null)
    {
        $objArtistGenre = is_null($objArtist) ? $this->model->find($strIdentity) : $this->findByArtistAndGenre($objArtist, $strIdentity);
        return !is_null($objArtistGenre) ? $objArtistGenre->delete() : false;
    }

    public function autoComplete(string $genre)
    {
        return $this->model->whereRaw("lower(artist_genre) like (?)", "%" . Util::lowerLabel($genre) . "%")->get(["artist_genre"])->unique("artist_genre")->pluck("artist_genre");
    }

    public function checkDuplicate(ArtistModel $objArtist, String $genre): Bool
    {
        return $this->model->where([
            "artist_id" => $objArtist->artist_id,
            "artist_genre" => $genre
        ])->exists();
    }

    public function findByArtistAndGenre(ArtistModel $objArtist, String $genre)
    {
        return $this->model->where([
            "artist_id" => $objArtist->artist_id,
            "artist_genre" => $genre
        ])->first();
    }
}
