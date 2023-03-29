<?php

namespace App\Repositories\Music\Artists;

use App\Helpers\Util;
use App\Helpers\Constant;
use App\Repositories\BaseRepository;
use App\Models\Music\Artist\Artist as ArtistModel;
use App\Models\Music\Artist\ArtistStyle as ArtistStyleModel;

class ArtistStyle extends BaseRepository
{
    /**
     * ArtistStyle constructor.
     * @param ArtistStyleModel $model
     * @param Style $styleModel
     */
    public function __construct(ArtistStyleModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param ArtistModel $artist
     * @param array $arrStyles
     * @return ArtistModel
     * @throws \Exception
     */
    public function createMultiple(ArtistModel $objArtist, array $arrStyles)
    {
        foreach ($arrStyles as $style) {
            $this->addStyleToArtist($objArtist, $style);
        }
        return $objArtist;
    }

    public function addStyleToArtist(ArtistModel $objArtist, String $style): ArtistStyleModel
    {
        $objTime = now();
        $objArtistStyle = $this->findByArtistAndStyle($objArtist, $style);
        if (!$objArtistStyle) {
            $objArtistStyle =   $this->create([
                "artist_id"   => $objArtist->artist_id,
                "artist_uuid" => $objArtist->artist_uuid,
                "artist_style"   => $style,
                "stamp_epoch" => time(),
                "stamp_date" => $objTime->toDateString(),
                "stamp_time" => $objTime->format("H:i:s.u"),
                "stamp_source"   => Constant::ARENA_SOURCE,
            ]);
        } else {
            $objArtistStyle->restore();
        }
        return $objArtistStyle;
    }


    public function autoComplete(string $strStyle)
    {
        return $this->model->whereRaw("lower(artist_style) like (?)", "%" . Util::lowerLabel(
            $strStyle
        ) . "%")->get([ "artist_style"])->unique("artist_style")->pluck("artist_style");
    }

    public function checkDuplicate(ArtistModel $objArtist, String $style): Bool
    {
        return $this->model->where([
            "artist_id" => $objArtist->artist_id,
            "artist_style" => $style
        ])->exists();
    }

    public function findByArtistAndStyle(ArtistModel $objArtist, String $style)
    {
        return $this->model->where([
            "artist_id" => $objArtist->artist_id,
            "artist_style" => $style
        ])->first();
    }

    public function deleteStyleFromArtist(String $strIdentity, ?ArtistModel $objArtist = null)
    {
        $objArtistStyle = is_null($objArtist) ? $this->model->find($strIdentity) : $this->findByArtistAndStyle($objArtist, $strIdentity);
        return !is_null($objArtistStyle) ? $objArtistStyle->delete() : false;
    }
}
