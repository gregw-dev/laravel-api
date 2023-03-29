<?php

namespace App\Repositories\Music\Artists;

use App\Helpers\Constant;
use App\Helpers\Util;
use App\Repositories\BaseRepository;
use App\Models\Music\Artist\Artist as ArtistModel;
use App\Models\Music\Artist\ArtistTheme as ArtistThemeModel;

class ArtistTheme extends BaseRepository
{
    /**
     * ArtistTheme constructor.
     * @param ArtistThemeModel $model
     */
    public function __construct(ArtistThemeModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param ArtistModel $artist
     * @param array $arrThemes
     * @return ArtistModel
     * @throws \Exception
     */
    public function createMultiple(ArtistModel $objArtist, array $arrThemes)
    {
        foreach ($arrThemes as $theme) {
        $this->addThemeToArtist($objArtist,$theme);
        }
        return $objArtist;
    }

    public function addThemeToArtist(ArtistModel $objArtist, $theme){
        $objArtistTheme = $this->findByArtistAndTheme($objArtist, $theme);
        if (!$objArtistTheme) {
            $objTime = now();
            $objArtistTheme =  $this->create([
                "artist_id"   => $objArtist->artist_id,
                "artist_uuid" => $objArtist->artist_uuid,
                "artist_theme"   => $theme,
                "stamp_epoch" => time(),
                "stamp_date" => $objTime->toDateString(),
                "stamp_time" => $objTime->format("H:i:s.u"),
                "stamp_source"   => Constant::ARENA_SOURCE,
            ]);
        } else {
            $objArtistTheme->restore();
        }
        return $objArtistTheme;
    }

    public function deleteThemeFromArtist(String $strIdentity, ?ArtistModel $objArtist = null)
    {
    $objArtistTheme = is_null($objArtist) ? $this->model->find($strIdentity) : $this->findByArtistAndTheme($objArtist, $strIdentity);
    return !is_null($objArtistTheme) ? $objArtistTheme->delete() : false;
    }

    public function autoComplete(string $strTheme)
    {
        return $this->model->whereRaw("LOWER(artist_theme) LIKE (?)", "%" . Util::lowerLabel($strTheme) . "%")->get(["artist_theme"])->unique("artist_theme")->pluck("artist_theme");
    }

    public function checkDuplicate(ArtistModel $objArtist, String $theme): Bool
    {
        return $this->model->where([
            "artist_id" => $objArtist->artist_id,
            "artist_theme" => $theme
        ])->exists();
    }

    public function findByArtistAndTheme(ArtistModel $objArtist, String $theme)
    {
        return $this->model->where([
            "artist_id" => $objArtist->artist_id,
            "artist_theme" => $theme
        ])->first();
    }
}
