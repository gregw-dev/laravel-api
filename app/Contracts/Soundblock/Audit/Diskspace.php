<?php

namespace App\Contracts\Soundblock\Audit;

use App\Models\Soundblock\Projects\Project;
use App\Models\Soundblock\Accounts\Account;

interface Diskspace {
    public function save(Project $objProject, int $fileSize);
    public function saveAccountDiskspace(Account $objAccount, int $fileSize);
    public function getByDate(string $strDate);
    public function getSumByDateRange(string $strStartDate, string $strEndDate);
}
