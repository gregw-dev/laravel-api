<?php

namespace App\Repositories\Soundblock\Payments;

use App\Models\Soundblock\Platform;
use App\Models\Users\User;
use Carbon\Carbon;
use Util;
use App\Repositories\BaseRepository;
use App\Models\Soundblock\Projects\Project as ProjectModel;
use App\Repositories\Soundblock\Platform as PlatformRepository;
use App\Models\Soundblock\Projects\Contracts\Contract as ContractModel;
use App\Models\Soundblock\Payments\MusicUserPayment as MusicUserPaymentModel;
use App\Contracts\Soundblock\Contracts\SmartContracts as SmartContractService;
use App\Repositories\Soundblock\ReportRevenueMusic as ReportRevenueMusicRepository;
use App\Repositories\Soundblock\Payments\MusicUserBalancePayment as MusicUserBalancePaymentRepository;

class MusicUserPayment extends BaseRepository {

    /** @var ReportRevenueMusicRepository */
    private ReportRevenueMusicRepository $reportRevenueMusicRepo;
    /** @var SmartContractService */
    private SmartContractService $contractsService;
    /** @var MusicUserBalancePayment */
    private MusicUserBalancePayment $musicUserBalancePaymentRepo;
    /** @var PlatformRepository */
    private PlatformRepository $platformRepo;

    /**
     * ReportProjectUser constructor.
     * @param MusicUserPaymentModel $objMusicUserPayment
     * @param ReportRevenueMusicRepository $reportRevenueMusicRepo
     * @param SmartContractService $contractsService
     * @param MusicUserBalancePayment $musicUserBalancePaymentRepo
     * @param PlatformRepository $platformRepo
     * @param\ SmartContractService $contractsService
     */
    public function __construct(MusicUserPaymentModel $objMusicUserPayment, ReportRevenueMusicRepository $reportRevenueMusicRepo,
                                 SmartContractService $contractsService, MusicUserBalancePaymentRepository $musicUserBalancePaymentRepo,
                                PlatformRepository $platformRepo) {
        $this->model = $objMusicUserPayment;
        $this->reportRevenueMusicRepo = $reportRevenueMusicRepo;
        $this->contractsService = $contractsService;
        $this->musicUserBalancePaymentRepo = $musicUserBalancePaymentRepo;
        $this->platformRepo = $platformRepo;
    }

    /**
     * @param ProjectModel $objProject
     * @param string $dateStarts
     * @param string $dateEnds
     * @param ContractModel $objContract
     * @param string|null $strPaymentMemo
     * @return bool
     * @throws \Exception
     */
    public function storeUserRevenueByProjectAndDates(ProjectModel $objProject, string $dateStarts, string $dateEnds, ContractModel $objContract): bool{
        $arrUsersBalance = [];
        $objApplePlatform = $this->platformRepo->findByName("Apple Music");
        $objReports = $this->reportRevenueMusicRepo->findProjectsByProjectAndDate($objProject->project_uuid, $dateStarts, $dateEnds);

        foreach ($objReports->groupBy("platform_id") as $intPlatformId => $objPlatformReports) {
            $strPaymentMemo = "";
            foreach ($objPlatformReports->groupBy("report_currency") as $curr => $report) {
                if ($objApplePlatform->platform_id == $intPlatformId) {
                    $strPaymentMemo = implode(", ", array_unique($report->pluck("payment_memo")->toArray()));
                }
                $arrReport = $this->contractsService->calculateUserPayoutBetweenDates($objContract, $dateStarts, $dateEnds, $report->sum("report_revenue"), $report->sum("report_revenue_usd"));
                foreach ($arrReport["users"] as $arrUser) {
                    $objModel = $this->model->where("date_starts", $arrReport["date_start"])
                        ->where("date_ends", $arrReport["date_end"])
                        ->where("project_id", $objProject->project_id)
                        ->where("platform_id", $intPlatformId)
                        ->where("user_id", $arrUser["user_id"])
                        ->where("report_currency", $curr)
                        ->first();

                    if (is_null($objModel)) {
                        $floatUserBalanceRevenue = $arrUser["revenue_usd"];
                        $this->model->create([
                            "row_uuid" => Util::uuid(),
                            "project_id" => $objProject->project_id,
                            "project_uuid" => $objProject->project_uuid,
                            "platform_id" => $intPlatformId,
                            "platform_uuid" => Platform::find($intPlatformId)->platform_uuid,
                            "user_id" => $arrUser["user_id"],
                            "user_uuid" => $arrUser["user_uuid"],
                            "date_starts" => $arrReport["date_start"],
                            "date_ends" => $arrReport["date_end"],
                            "report_currency" => $curr,
                            "report_revenue" => $arrUser["revenue"],
                            "report_revenue_usd" => $arrUser["revenue_usd"],
                            "payment_memo" => $strPaymentMemo
                        ]);
                    } else {
                        if (number_format($arrUser["revenue_usd"], 10) != number_format($objModel->report_revenue_usd, 10)) {
                            $floatUserBalanceRevenue = (float)$arrUser["revenue_usd"] - (float)$objModel->report_revenue_usd;
                            $objModel->update([
                                "report_revenue" => $arrUser["revenue"],
                                "report_revenue_usd" => $arrUser["revenue_usd"],
                                "payment_memo" => $strPaymentMemo
                            ]);
                        }
                    }

                    if (isset($floatUserBalanceRevenue)) {
                        if (isset($arrUsersBalance[$arrUser["user_id"]][$intPlatformId])) {
                            $arrUsersBalance[$arrUser["user_id"]][$intPlatformId] += (float)$floatUserBalanceRevenue;
                        } else {
                            $arrUsersBalance[$arrUser["user_id"]][$intPlatformId] = (float)$floatUserBalanceRevenue;
                        }
                    }
                }
            }
        }

        $this->musicUserBalancePaymentRepo->createFromReports($arrUsersBalance, $objProject, $dateStarts, $dateEnds, $strPaymentMemo);

        return (true);
    }

    public function getByDates(string $strDateStarts, string $strDateEnds)
    {
        return $this->model->where("date_starts",$strDateStarts)
            ->where("date_ends",$strDateEnds)
            ->get();
    }

    public function getByUserAndProjects(string $strUserUuid, array $arrProjectsUuids, $strPlatformUuid = null, $dateStarts = null, $dateEnds = null)
    {
        $query = $this->model->whereIn("project_uuid", $arrProjectsUuids)->where("user_uuid", $strUserUuid);

        if (is_string($strPlatformUuid)) {
            $query = $query->where("platform_uuid", $strPlatformUuid);
        }

        if (is_string($dateStarts) && is_string($dateEnds)) {
            $strDateStarts = Carbon::createFromFormat("Y-m", $dateStarts)->startOfMonth()->format("Y-m-d");
            $strDateEnds = Carbon::createFromFormat("Y-m", $dateEnds)->endOfMonth()->format("Y-m-d");
            $query = $query->where("date_ends", ">=", $strDateStarts)->where("date_ends", "<=", $strDateEnds);
        }

        return ($query->get());
    }
}
