<?php

namespace App\Repositories\Soundblock\Data;

use Util;
use App\Models\Soundblock\Data\AllExchangeRate;
use App\Repositories\BaseRepository;

class AllExchangeRates extends BaseRepository {
    /**
     * ProjectsRoles constructor.
     * @param \App\Models\Soundblock\Data\AllExchangeRate $model
     */
    public function __construct(AllExchangeRate $model) {
        $this->model = $model;
    }

    public function findAvgByDates(string $date_stars, string $dateEnds){
        $arrResponse = [];
        $objRecords = $this->model->whereBetween("data_exchange_date", [$date_stars, $dateEnds])->get(["data_currency_code", "data_rate"]);

        foreach ($objRecords->groupBy("data_currency_code") as $strCode => $objCodeRecords) {
            if (count($objCodeRecords) < 20) {
                throw new \Exception("Not enough data for avg currency rate.");
            }
            $arrResponse[$strCode] = $objCodeRecords->avg("data_rate");
        }

        return ($arrResponse);
    }

    public function crateOrUpdate(array $arrData) {
        $objRate = $this->model
            ->where("data_exchange_date", $arrData["data_exchange_date"])
            ->where("data_currency_code", $arrData["data_currency_code"])
            ->first();

        if ($objRate) {
            $objRate->update($arrData);
        } else {
            $arrData["data_uuid"] = Util::uuid();
            $this->model->create($arrData);
        }

        return (true);
    }

}
