<?php

namespace App\Services\Soundblock\Payment;

use Carbon\Carbon;
use App\Contracts\Soundblock\Contracts\SmartContracts as SmartContractService;
use App\Repositories\Soundblock\Payments\MusicUserPayment as MusicUserPaymentRepo;
use App\Repositories\Soundblock\ReportRevenueMusic as ReportRevenueMusicRepository;

class MusicUserPayment
{
    /** @var MusicUserPaymentRepo */
    private MusicUserPaymentRepo $musicUserPaymentRepo;
    /** @var ReportRevenueMusicRepository */
    private ReportRevenueMusicRepository $reportRevenueMusicMatchedRepo;
    /** @var SmartContractService */
    private SmartContractService $smartContractService;

    /**
     * MusicUserPayment constructor.
     * @param MusicUserPaymentRepo $musicUserPaymentRepo
     * @param SmartContractService $smartContractService
     * @param ReportRevenueMusicRepository $reportRevenueMusicMatchedRepo
     */
    public function __construct(MusicUserPaymentRepo $musicUserPaymentRepo, SmartContractService $smartContractService,
                                ReportRevenueMusicRepository $reportRevenueMusicMatchedRepo)
    {
        $this->musicUserPaymentRepo = $musicUserPaymentRepo;
        $this->reportRevenueMusicMatchedRepo = $reportRevenueMusicMatchedRepo;
        $this->smartContractService = $smartContractService;
    }

    /**
     * @param $objUser
     * @param $arrProjects
     * @param null $strPlatformUuid
     * @param null $dateStarts
     * @param null $dateEnds
     * @return array
     */
    public function getUserRevenueByProjects($objUser, $arrProjects, $strPlatformUuid = null, $dateStarts = null, $dateEnds = null){
        $arrReports = [];
        $objUserReports = $this->musicUserPaymentRepo->getByUserAndProjects($objUser->user_uuid, $arrProjects, $strPlatformUuid, $dateStarts, $dateEnds);

        foreach ($objUserReports->groupBy("date_ends") as $strDate => $objPayments) {
            $strDate = Carbon::parse($strDate)->format("Y-m");

            foreach ($objPayments as $objPayment) {
                if (empty($arrReports[$strDate])) {
                    $arrReports[$strDate] = $objPayment->report_revenue_usd;
                } else {
                    $arrReports[$strDate] += $objPayment->report_revenue_usd;
                }
            }
        }
        ksort($arrReports);

        return ($arrReports);
    }

    public function getProjectTracksReport($objUser, $arrProjectsUuids, $strPlatformUuid = null, $dateStarts = null, $dateEnds = null){
        $arrReports = [];
        $objMatchedReports = $this->reportRevenueMusicMatchedRepo->findAllByProjectsAndDates($arrProjectsUuids, $strPlatformUuid, $dateStarts, $dateEnds);

        foreach ($objMatchedReports->groupBy("project_id") as $objProjectPayments) {
            $objProject = $objProjectPayments->first()->project;
            $objContract = $objProject->contracts()->where("flag_status", "Active")->orderBy("contract_version", "desc")->first();

            if ($objContract) {
                foreach ($objProjectPayments->groupBy("track_id") as $objProjectTrackPayments) {
                    $objTrack = $objProjectTrackPayments->first()->track;

                    foreach ($objProjectTrackPayments->groupBy("date_ends") as $objProjectTrackDatePayments) {
                        $strDateStarts = $objProjectTrackDatePayments->first()->date_starts;
                        $strDateSEnds = $objProjectTrackDatePayments->first()->date_ends;
                        try {
                            $arrReport = $this->smartContractService->calculateUserPayoutBetweenDates(
                                $objContract,
                                $strDateStarts,
                                $strDateSEnds,
                                $objProjectTrackDatePayments->sum("report_revenue"),
                                $objProjectTrackDatePayments->sum("report_revenue_usd")
                            );

                            if (array_key_exists($objUser->user_id, $arrReport["users"])) {
                                $floatUserTrackRevenue = $arrReport["users"][$objUser->user_id]["revenue_usd"];
                            } else {
                                $floatUserTrackRevenue = 0;
                            }
                        } catch (\Exception $exception) {
                            $floatUserTrackRevenue = 0;
                        }

                        if (isset($arrReports[$objProject->project_uuid][$objTrack->track_uuid])) {
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["all"] += $objProjectTrackDatePayments->sum("report_revenue_usd");
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["user"] += $floatUserTrackRevenue;
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["project"] = $objProject->project_title;
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["track"] = $objTrack->file->file_title;
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["track_number"] = $objTrack->track_number;
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["track_volume"] = $objTrack->track_volume_number;
                        } else {
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["all"] = $objProjectTrackDatePayments->sum("report_revenue_usd");
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["user"] = $floatUserTrackRevenue;
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["project"] = $objProject->project_title;
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["track"] = $objTrack->file->file_title;
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["track_number"] = $objTrack->track_number;
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["track_volume"] = $objTrack->track_volume_number;
                        }
                    }
                }
            } else {
                $floatUserTrackRevenue = 0;
                foreach ($objProjectPayments->groupBy("track_id") as $objProjectTrackPayments) {
                    $objTrack = $objProjectTrackPayments->first()->track;

                    foreach ($objProjectTrackPayments->groupBy("date_ends") as $objProjectTrackDatePayments) {
                        if (isset($arrReports[$objProject->project_uuid][$objTrack->track_uuid])) {
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["all"] += $objProjectTrackDatePayments->sum("report_revenue_usd");
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["user"] += $floatUserTrackRevenue;
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["project"] = $objProject->project_title;
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["track"] = $objTrack->file->file_title;
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["track_number"] = $objTrack->track_number;
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["track_volume"] = $objTrack->track_volume_number;
                        } else {
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["all"] = $objProjectTrackDatePayments->sum("report_revenue_usd");
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["user"] = $floatUserTrackRevenue;
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["project"] = $objProject->project_title;
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["track"] = $objTrack->file->file_title;
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["track_number"] = $objTrack->track_number;
                            $arrReports[$objProject->project_uuid][$objTrack->track_uuid]["track_volume"] = $objTrack->track_volume_number;
                        }
                    }
                }
            }
        }

        return ($arrReports);
    }
}
