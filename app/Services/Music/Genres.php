<?php

namespace App\Services\Music;

use App\Contracts\Music\Genre as GenreContract;
use App\Repositories\Music\Artists\ArtistGenre;

class Genres implements GenreContract {
    /**
     * @var Genre
     */
    private ArtistGenre $objGenreRepository;

    /**
     * Genres constructor.
     * @param Genre $objGenreRepository
     */
    public function __construct(ArtistGenre $objGenreRepository) {
        $this->objGenreRepository = $objGenreRepository;
    }

    public function autoComplete(string $genre) {
        return $this->objGenreRepository->autoComplete($genre);
    }
}
