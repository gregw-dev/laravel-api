<?php

namespace App\Repositories\Soundblock;

use App\Models\Soundblock\Contributor as ContributorModel;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

class Contributor extends BaseRepository {
    public function __construct(ContributorModel $model) {
        $this->model = $model;
    }

    /**
     * @param string $strContributor
     * @return mixed
     */
    public function findByName(string $strContributor) {
        return ($this->model->where("contributor_name", $strContributor)->first());
    }

    /**
     * @param array $arrData
     * @return mixed
     */
    public function typeahead(array $arrData) {
        $objQuery = $this->model->newQuery();

        if (isset($arrData["contributor"])) {
            $objQuery = $objQuery->where("contributor_name", "like", "%{$arrData["contributor"]}%");
        }

        if (isset($arrData["account"])) {
            $objQuery = $objQuery->whereHas("accounts", function (Builder $query) use ($arrData) {
                $query->where("account_uuid", $arrData["account"]);
            });
        }

        return ($objQuery->get());
    }

    /**
     * @param string $strAccount_uuid
     * @return mixed
     */
    public function findAllByAccount(string $strAccount_uuid){
        return ($this->model->where("account_uuid", $strAccount_uuid)->orderBy("contributor_name", "asc")->get());
    }

    /**
     * @param string $strAccount_uuid
     * @param string $name
     * @return mixed
     */
    public function findByAccountAndName(string $strAccount_uuid, string $name){
        return ($this->model->where("account_uuid", $strAccount_uuid)->where("contributor_name", $name)->first());
    }

    /**
     * @param string $strContributorUuid
     * @return mixed
     */
    public function delete(string $strContributorUuid){
        return ($this->model->where("contributor_uuid", $strContributorUuid)->delete());
    }

    /**
     * @param string $strContributorUuid
     * @param bool $boolFlag
     * @return mixed
     */
    public function setFlagPermanent(string $strContributorUuid, bool $boolFlag){
        return ($this->model->where("contributor_uuid", $strContributorUuid)->update(['flag_permanent' => $boolFlag]));
    }
}
