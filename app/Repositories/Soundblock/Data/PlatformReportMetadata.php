<?php

namespace App\Repositories\Soundblock\Data;

use Carbon\Carbon;
use App\Repositories\BaseRepository;
use App\Models\Soundblock\Data\PlatformReportMetadata as PlatformReportAppleModel;

class PlatformReportMetadata extends BaseRepository {
    /**
     * ProjectsRoles constructor.
     * @param PlatformReportAppleModel $model
     */
    public function __construct(PlatformReportAppleModel $model) {
        $this->model = $model;
    }

    /**
     * @param string|null $strPlatform
     * @param string|null $strDate
     * @param string|null $strStatus
     * @param int $intPerPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function findByPlatformDateAndStatus(string $strPlatform = null, string $strDate = null, string $strStatus = null, int $intPerPage = 10){
        $query = $this->model->newQuery();

        if ($strPlatform) {
            $query = $query->where("platform_uuid", $strPlatform);
        }

        if ($strDate) {
            $objCarbonDate = Carbon::createFromFormat("Y-m", $strDate);
            $query = $query->whereYear("date_ends", $objCarbonDate->year)->whereMonth("date_ends", $objCarbonDate->month);
        }

        if ($strStatus) {
            $query = $query->where("status", $strStatus);
        }

        return ($query->orderBy("stamp_created_at", "desc")->paginate($intPerPage));
    }

    public function checkIfFileExists(string $strFileName){
        return ($this->model->where("report_file_name", $strFileName)->exists());
    }

    public function canProcessFile(string $strFileName){
        if (!$this->model->where("report_file_name", $strFileName)->exists()) {
            return (true);
        } else {
            foreach ($this->model->where("report_file_name", $strFileName)->get() as $objRecord) {
                if ($objRecord->status != "Failed") {
                    return (false);
                }
            }

            return (true);
        }
    }
}
