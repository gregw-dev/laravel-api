<?php


namespace App\Repositories\Music;

use App\Helpers\Util;
use App\Repositories\BaseRepository;

class Style extends BaseRepository {
    /**
     * GenreRepository constructor.
     * @param \App\Models\Music\Style $model
     */
    public function __construct(\App\Models\Music\Artist\ArtistStyle $model) {
        $this->model = $model;
    }

    public function autocomplete(string $style) {
        return $this->model->whereRaw("lower(style_name) like (?)", "%" . Util::lowerLabel($style) . "%")->get();
    }
}
