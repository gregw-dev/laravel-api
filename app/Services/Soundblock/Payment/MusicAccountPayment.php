<?php

namespace App\Services\Soundblock\Payment;

use Carbon\Carbon;
use App\Repositories\Soundblock\Platform as PlatformRepo;
use App\Repositories\Soundblock\Payments\MusicAccountPayment as MusicAccountPaymentRepo;
use App\Repositories\Soundblock\Payments\MusicProjectPayment as MusicProjectPaymentRepo;

class MusicAccountPayment
{
    private MusicAccountPaymentRepo $musicAccountPaymentRepo;
    /** @var MusicProjectPaymentRepo */
    private MusicProjectPaymentRepo $musicProjectPaymentRepo;
    /** @var PlatformRepo */
    private PlatformRepo $platformRepo;

    /**
     * MusicAccountPayment constructor.
     * @param MusicAccountPaymentRepo $musicAccountPaymentRepo
     * @param MusicProjectPaymentRepo $musicProjectPaymentRepo
     * @param PlatformRepo $platformRepo
     */
    public function __construct(MusicAccountPaymentRepo $musicAccountPaymentRepo, MusicProjectPaymentRepo $musicProjectPaymentRepo,
                                PlatformRepo $platformRepo)
    {
        $this->musicAccountPaymentRepo = $musicAccountPaymentRepo;
        $this->musicProjectPaymentRepo = $musicProjectPaymentRepo;
        $this->platformRepo = $platformRepo;
    }

    public function buildMusicAccountPaymentReport($strAccountUuid, $dateStarts = null, $dateEnds = null, $strPlatformUuid = null): array
    {
        $objProjectPayments = $this->musicAccountPaymentRepo->getBetweenDates($strAccountUuid, $dateStarts, $dateEnds, $strPlatformUuid);
        $arrReports = [];
        $arrPlatforms = [];

        foreach ($objProjectPayments->groupBy("date_ends") as $strDate => $objPayments) {
            $strDate = Carbon::parse($strDate)->format("Y-m");

            foreach ($objPayments as $objPayment) {
                $arrPlatforms[] = $objPayment->platform_uuid;
                if (empty($arrReports[$strDate][$objPayment->platform_uuid])) {
                    $arrReports[$strDate][$objPayment->platform_uuid] = $objPayment->payment_amount;
                } else {
                    $arrReports[$strDate][$objPayment->platform_uuid] += $objPayment->payment_amount;
                }
            }
        }
        ksort($arrReports);

        $objPlatforms = $this->platformRepo->findMany(array_unique($arrPlatforms))->toArray();
        foreach ($arrReports as $strDate => $arrDateReport) {
            foreach ($arrDateReport as $strPlatform => $value) {
                $key = array_search($strPlatform, array_column($objPlatforms, "platform_uuid"));
                $strPlatformName = $objPlatforms[$key]["name"];
                $arrReports[$strDate][$strPlatformName] = $value;
                unset($arrReports[$strDate][$strPlatform]);
            }
        }

        return $arrReports;
    }

    public function buildMusicProjectPaymentReport($strProjectUuid, $dateStarts = null, $dateEnds = null, $strPlatformUuid = null): array
    {
        $objProjectPayments = $this->musicProjectPaymentRepo->getBetweenDates($strProjectUuid, $dateStarts, $dateEnds, $strPlatformUuid);
        $arrReports = [];
        $arrPlatforms = [];

        foreach ($objProjectPayments->groupBy("date_ends") as $strDate => $objPayments) {
            $strDate = Carbon::parse($strDate)->format("Y-m");

            foreach ($objPayments as $objPayment) {
                $arrPlatforms[] = $objPayment->platform_uuid;
                if (empty($arrReports[$strDate][$objPayment->platform_uuid])) {
                    $arrReports[$strDate][$objPayment->platform_uuid] = $objPayment->payment_amount;
                } else {
                    $arrReports[$strDate][$objPayment->platform_uuid] += $objPayment->payment_amount;
                }
            }
        }

        $objPlatforms = $this->platformRepo->findMany(array_unique($arrPlatforms))->toArray();
        foreach ($arrReports as $strDate => $arrDateReport) {
            foreach ($arrDateReport as $strPlatform => $value) {
                $key = array_search($strPlatform, array_column($objPlatforms, "platform_uuid"));
                $strPlatformName = $objPlatforms[$key]["name"];
                $arrReports[$strDate][$strPlatformName] = $value;
                unset($arrReports[$strDate][$strPlatform]);
            }
        }

        return $arrReports;
    }
}
