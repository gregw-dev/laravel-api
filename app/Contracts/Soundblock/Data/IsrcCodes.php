<?php

namespace App\Contracts\Soundblock\Data;

use App\Models\Soundblock\Data\IsrcCode;

interface IsrcCodes
{
    public function getUnused();

    public function useIsrc(IsrcCode $isrcCode);

    public function releaseIsrc(IsrcCode $isrcCode);

    public function findByIsrc(string $isrcCode);

    public function isAssigned(string $isrcCode);
}
