<?php


namespace App\Services\Soundblock\Data;

use App\Contracts\Soundblock\Data\IsrcCodes as IsrcCodesContract;
use App\Models\Soundblock\Data\IsrcCode;

class IsrcCodes implements IsrcCodesContract {

    private \App\Repositories\Soundblock\IsrcCodes $isrcCodesRepo;

    public function __construct(\App\Repositories\Soundblock\IsrcCodes $isrcCodesRepo) {
        $this->isrcCodesRepo = $isrcCodesRepo;
    }

    public function getUnused()
    {
        return $this->isrcCodesRepo->geUnused();
    }

    public function useIsrc(IsrcCode $isrcCode)
    {
        return $this->isrcCodesRepo->useIsrc($isrcCode);
    }

    public function releaseIsrc(IsrcCode $isrcCode)
    {
        return $this->isrcCodesRepo->releaseIsrc($isrcCode);
    }

    public function findByIsrc(string $isrcCode)
    {
        return $this->isrcCodesRepo->findByIsrc($isrcCode);
    }

    public function isAssigned(string $isrcCode)
    {
        return $this->isrcCodesRepo->isAssigned($isrcCode);
    }
}
