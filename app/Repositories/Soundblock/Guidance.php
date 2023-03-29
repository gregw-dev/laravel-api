<?php

namespace App\Repositories\Soundblock;

use App\Repositories\BaseRepository;
use App\Models\Soundblock\Guidance as GuidanceModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Guidance extends BaseRepository
{

    protected Model $model;
    /**
     * @param GuidanceModel $guidanceModel
     */
    public function __construct(GuidanceModel $guidanceModel)
    {
        $this->model = $guidanceModel;
    }
    /**
     * @param string $strGuideref
     * @return ?GuidanceModel
     */
    public function findByGuideRef(String $strGuideRef)
    {
        return $this->model->where("guide_ref", $strGuideRef)->first();
    }

    public function findAll()
    {
        return $this->model->all();
    }
    /**
     * @param array $arrParams
     */
    public function listAll(array $arrParams)
    {
        $query = $this->model->query();

        if (isset($arrParams["search"])) {
            $strSearch = $arrParams["search"];
            $query->where("guide_ref", "LIKE", "%$strSearch%")->orWhere("guide_html", "LIKE", "%$strSearch%")->orWhere("guide_rating", "LIKE", "%$strSearch%")->orWhere("guide_title", "LIKE", "%$strSearch%");
        }

        if (isset($arrParams["title"])) {
            $strTitle = $arrParams["title"];
            $query->where("guide_title", "LIKE", "%$strTitle%");
        }

        if (isset($arrParams["guide_ref"])) {
            $strGuideRef = $arrParams["guide_ref"];
            $query->where("guide_ref", "LIKE", "%$strGuideRef%");
        }

        if (isset($arrParams["flag_active"])) {
            $intFlagActive = $arrParams["flag_active"];
            $query->where("flag_active", $intFlagActive);
        }

        return ($query->paginate($arrParams["per_page"] ?? 10));
    }

    public function listRefs($strFilter)
    {
        $query = $this->model->query();
        if ($strFilter) {
            $strValue = strtoupper($strFilter);
            $query->where(DB::raw("UPPER(guide_ref)"), "LIKE", "%$strValue%");
        }
        return $query->get(["guide_ref"]);
    }

    public function listTitles($strFilter)
    {
        $query = $this->model->query();
        if ($strFilter) {
            $strValue = strtoupper($strFilter);
            $query->where(DB::raw("UPPER(guide_title)"), "LIKE", "%$strValue%");
        }
        return $query->get(["guide_title"]);
    }
}
