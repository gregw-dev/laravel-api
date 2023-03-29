<?php

namespace App\Services\Music;

use App\Repositories\Music\Artists\ArtistTheme;
use App\Contracts\Music\Themes as ThemesContract;

class Themes implements ThemesContract {
    /**
     * @var ArtistTheme
     */
    private ArtistTheme $objThemeRepository;

    /**
     * Genres constructor.
     * @param ArtistTheme $objThemeRepository
     */
    public function __construct(ArtistTheme $objThemeRepository) {
        $this->objThemeRepository = $objThemeRepository;
    }

    public function autoComplete(string $theme) {
        return $this->objThemeRepository->autoComplete($theme);
    }
}
