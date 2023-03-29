<?php

namespace App\Repositories\Soundblock;

use App\Repositories\BaseRepository;
use App\Models\Soundblock\Reports\RevenueMusicUnmatched as RevenueMusicUnmatchedModel;

class ReportRevenueMusicUnmatched extends BaseRepository {

    public function __construct(RevenueMusicUnmatchedModel $objReportRevenue) {
        $this->model = $objReportRevenue;
    }
}
