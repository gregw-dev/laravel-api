<?php


namespace App\Services\Music;


use App\Contracts\Music\Moods as MoodsContract;
use App\Repositories\Music\Artists\ArtistMood;

class Moods implements MoodsContract {
    /**
     * @var ArtistMood
     */
    private ArtistMood $objMoodRepository;

    public function __construct(ArtistMood $objMoodRepository) {
        $this->objMoodRepository = $objMoodRepository;
    }

    public function autoComplete(string $mood) {
        return $this->objMoodRepository->autoComplete($mood);
    }
}
