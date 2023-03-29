<?php


namespace App\Repositories\Music;

use App\Helpers\Util;
use App\Models\Music\Artist\ArtistGenre as GenreModel;
use App\Repositories\BaseRepository;

class Genre extends BaseRepository {
    /**
     * GenreRepository constructor.
     * @param GenreModel $model
     */
    public function __construct(GenreModel $model) {
        $this->model = $model;
    }

    public function autocomplete(string $genre) {
        return $this->model->whereRaw("lower(genre_name) like (?)", "%" . Util::lowerLabel($genre) . "%")->unique()->get();
    }

    public function findAll(array $arrUuid) {
        return $this->model->whereIn("genre_uuid", $arrUuid)->get();
    }
}
