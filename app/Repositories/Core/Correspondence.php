<?php

namespace App\Repositories\Core;

use App\Models\BaseModel;
use App\Models\Core\Correspondence as CorrespondenceModel;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class Correspondence extends BaseRepository {
    /**
     * CorrespondenceRepository constructor.
     * @param CorrespondenceModel $correspondence
     */
    public function __construct(CorrespondenceModel $correspondence){
        $this->model = $correspondence;
    }

    /**
     * @param int $per_page
     * @param array $arrFilters
     * @return array
     */
    public function findAll(int $per_page, array $arrFilters){
        $query = $this->model->newQuery() ->select();

        if (isset($arrFilters["date_start"])) {
            $query = $query->whereDate("core_correspondence." . BaseModel::CREATED_AT, ">=", $arrFilters["date_start"]);
        }

        if (isset($arrFilters["date_end"])) {
            $query = $query->whereDate("core_correspondence." . BaseModel::CREATED_AT, "<=", $arrFilters["date_end"]);
        }

        if (!array_key_exists("sort", $arrFilters)) {
            $query = $query->orderBy("core_correspondence." .BaseModel::CREATED_AT, "desc");
        }

        [$query, $availableMetaData] = $this->applyMetaFilters($arrFilters, $query);

        $objCorrespondences = $query->paginate($per_page);

        return ([$objCorrespondences, $availableMetaData]);
    }

    /**
     * @param int $per_page
     * @param array $arrFilters
     * @param array $arrAllowedApps
     * @return array
     */
    public function findAllWithAllowedApps(int $per_page, array $arrFilters, array $arrAllowedApps){
        $query = $this->model->newQuery() ->select();

        if (isset($arrFilters["date_start"])) {
            $query = $query->whereDate("core_correspondence." . BaseModel::CREATED_AT, ">=", $arrFilters["date_start"]);
        }

        if (isset($arrFilters["date_end"])) {
            $query = $query->whereDate("core_correspondence." . BaseModel::CREATED_AT, "<=", $arrFilters["date_end"]);
        }

        if (!array_key_exists("sort", $arrFilters)) {
            $query = $query->orderBy("core_correspondence." .BaseModel::CREATED_AT, "desc");
        }

        [$query, $availableMetaData] = $this->applyMetaFilters($arrFilters, $query);

        $query->whereHas("app", function($query) use ($arrAllowedApps) {
            $query->whereIn("app_name", $arrAllowedApps);
        });

        $objCorrespondences = $query->paginate($per_page);

        return ([$objCorrespondences, $availableMetaData]);
    }

    /**
     * @param string $correspondenceUUID
     * @return mixed
     */
    public function findByUuid(string $correspondenceUUID){
        $objCorrespondence = $this->model->where("correspondence_uuid", $correspondenceUUID)->first();
        return ($objCorrespondence);
    }

    public function checkDuplicate(string $strEmail, string $strSubject, string $strJson,$strColumnType) {
        $objCheck =  DB::table("core_correspondence_messages")
        ->join("core_correspondence","core_correspondence_messages.correspondence_id","=","core_correspondence.correspondence_id")
        ->where("core_correspondence_messages.user_email","=",$strEmail)
        ->where("core_correspondence_messages.email_{$strColumnType}","=",$strJson)
        ->get("email_subject");

        if(empty($objCheck)){
            return false;
        }
        foreach($objCheck as $objTest){
            if($objTest->email_subject==$strSubject){
                return true;
            }
        }
        return false;

    }

    /**
     * @param array $insertData
     * @return mixed
     */
    public function create(array $insertData){
        $objCorrespondence = $this->model->create($insertData);

        return ($objCorrespondence);
    }

    /**
     * @param string $correspondenceUUID
     * @param array $updateData
     * @return mixed
     */
    public function updateByUuid(string $correspondenceUUID, array $updateData){
        $objCorrespondence = $this->model->where("correspondence_uuid", $correspondenceUUID)->update($updateData);

        return ($objCorrespondence);
    }
}
