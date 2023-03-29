<?php

namespace App\Repositories\Soundblock\Data;

use App\Repositories\BaseRepository;
use App\Models\Soundblock\Data\PlatformReport as PlatformReportModel;

class PlatformReport extends BaseRepository {
    /**
     * ProjectsRoles constructor.
     * @param PlatformReportModel $model
     */
    public function __construct(PlatformReportModel $model) {
        $this->model = $model;
    }

    public function findByPlatformAndDates(string $strPlatformUuid, string $strYear, string $strMonth){
        return ($this->model->where("platform_uuid", $strPlatformUuid)->where("report_year", $strYear)->where("report_month", $strMonth)->first());
    }

    public function findReportsByYear(string $strYear){
        return ($this->model->where("report_year", $strYear)->get());
    }

    public function findLatestDateOfPlatformReport(string $strPlatformUuid){
        return ($this->model->where("platform_uuid", $strPlatformUuid)->orderBy("report_year", "desc")->orderBy("report_month", "desc")->first());
    }
}
