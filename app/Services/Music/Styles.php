<?php


namespace App\Services\Music;

use App\Repositories\Music\Artists\ArtistStyle;
use App\Contracts\Music\Styles as StylesContract;

class Styles implements StylesContract {
    /**
     * @var ArtistStyle
     */
    private ArtistStyle $objStyleRepository;

    /**
     * Styles constructor.
     * @param ArtistStyle $objStyleRepository
     */
    public function __construct(ArtistStyle $objStyleRepository) {
        $this->objStyleRepository = $objStyleRepository;
    }

    public function autoComplete(string $style) {
        return $this->objStyleRepository->autoComplete($style);
    }
}
