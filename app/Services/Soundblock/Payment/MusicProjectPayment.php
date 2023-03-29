<?php


namespace App\Services\Soundblock\Payment;


use App\Models\Soundblock\Projects\Project as ProjectModel;
use App\Repositories\Soundblock\Payments\MusicProjectPayment as MusicProjectPaymentRepo;

class MusicProjectPayment
{
    private MusicProjectPaymentRepo $musicProjectPaymentRepo;

    public function __construct(MusicProjectPaymentRepo $musicProjectPaymentRepo)
    {
        $this->musicProjectPaymentRepo = $musicProjectPaymentRepo;
    }

    public function buildMusicProjectPaymentReport(ProjectModel $objProject, $dateStarts, $dateEnds): array
    {
        $objProjectPayments=$this->musicProjectPaymentRepo->getBetweenDates($objProject,$dateStarts,$dateEnds);
        $arrReports=[];
        foreach ($objProjectPayments->groupBy("date_ends") as $strDate=>$objPayments) {
                $arrReports[$strDate]["revenue"]=$objPayments->sum("payment_amount");
        }
        return $arrReports;
    }
}
