<?php

namespace App\Repositories\Soundblock\Data;

use Util;
use App\Models\Soundblock\Data\ExchangeRate;
use App\Repositories\BaseRepository;

class ExchangeRates extends BaseRepository {
    /**
     * ProjectsRoles constructor.
     * @param \App\Models\Soundblock\Data\ExchangeRate $model
     */
    public function __construct(ExchangeRate $model) {
        $this->model = $model;
    }

    public function findAvgByCodeAndDates(string $currCode, string $date_stars, string $dateEnds){
        return ($this->model->whereBetween("exchange_date", [$date_stars, $dateEnds])->avg("usd_to_" . $currCode));
    }

    public function crateOrUpdate(array $arrData) {
        $objRate = $this->model->where("exchange_date", $arrData["exchange_date"])->first();

        if ($objRate) {
            $objRate->update($arrData);
        } else {
            $arrData["data_uuid"] = Util::uuid();
            $this->model->create($arrData);
        }

        return (true);
    }

}
