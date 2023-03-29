<?php

namespace App\Http\Controllers\Soundblock;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\Common\Common;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Services\Common\Common as CommonService;
use App\Services\Soundblock\Project as ProjectService;
use App\Services\Soundblock\Reports as ReportsService;
use App\Http\Requests\Soundblock\Reports\Report as ReportRequest;
use App\Services\Soundblock\Payment\MusicUserPayment as MusicUserPaymentService;
use App\Services\Soundblock\Payment\MusicAccountPayment as MusicAccountPaymentService;
use App\Services\Soundblock\Payment\MusicProjectPayment as MusicProjectPaymentService;
use App\Http\Requests\Soundblock\Reports\Revenue\PaymentsReport as PaymentsReportRequest;
use App\Repositories\Soundblock\Platform as PlatformRepository;
use App\Repositories\Soundblock\Data\PlatformReport as PlatformReportRepository;
use App\Repositories\Soundblock\ReportRevenueMusic as ReportRevenueMusicRepository;

/**
 * @group Soundblock
 *
 * Soundblock routes
 */
class Reports extends Controller {
    /** @var ProjectService */
    private ProjectService $objProjectService;
    /** @var Common */
    private Common $objPlanService;
    private MusicProjectPaymentService $objMusicProjectPayment;
    private MusicAccountPaymentService $objMusicAccountPayment;
    /** @var ReportsService */
    private ReportsService $reportsService;
    /** @var CommonService */
    private CommonService $commonService;
    /** @var MusicUserPaymentService */
    private MusicUserPaymentService $musicUserPaymentService;
    /** @var PlatformRepository */
    private PlatformRepository $platformRepo;
    /** @var PlatformReportRepository */
    private PlatformReportRepository $platformReportRepo;
    /** @var ReportRevenueMusicRepository */
    private ReportRevenueMusicRepository $reportRevenueMusicRepo;

    /**
     * Reports constructor.
     * @param ProjectService $objProjectService
     * @param Common $objPlanService
     * @param ReportsService $reportsService
     * @param MusicProjectPaymentService $objMusicProjectPayment
     * @param MusicAccountPaymentService $objMusicAccountPayment
     * @param CommonService $commonService
     * @param MusicUserPaymentService $musicUserPaymentService
     * @param PlatformRepository $platformRepo
     * @param PlatformReportRepository $platformReportRepo
     * @param ReportRevenueMusicRepository $reportRevenueMusicRepo
     */
    public function __construct(ProjectService $objProjectService, Common $objPlanService, ReportsService $reportsService,
                                MusicProjectPaymentService $objMusicProjectPayment,
                                MusicAccountPaymentService $objMusicAccountPayment, CommonService $commonService,
                                MusicUserPaymentService $musicUserPaymentService, PlatformRepository $platformRepo,
                                PlatformReportRepository $platformReportRepo, ReportRevenueMusicRepository $reportRevenueMusicRepo) {
        $this->objProjectService = $objProjectService;
        $this->objPlanService = $objPlanService;
        $this->objMusicProjectPayment = $objMusicProjectPayment;
        $this->objMusicAccountPayment = $objMusicAccountPayment;
        $this->reportsService = $reportsService;
        $this->commonService = $commonService;
        $this->musicUserPaymentService = $musicUserPaymentService;
        $this->platformRepo = $platformRepo;
        $this->platformReportRepo = $platformReportRepo;
        $this->reportRevenueMusicRepo = $reportRevenueMusicRepo;
    }

    public function getAvailableMetaDataForUser() {
        $objUser = Auth::user();
        $objData = $this->reportsService->getAvailableUsersProjects($objUser);

        return ($this->apiReply($objData, "", Response::HTTP_OK));
    }

    public function getAvailableUsageData(){
        $objUser = Auth::user();
        $objData = $this->reportsService->getAvailableUsersProjectsforUsage($objUser);

        return ($this->apiReply($objData, "", Response::HTTP_OK));
    }

    public function getProjectReport(string $project, ReportRequest $objRequest) {
        $objUser = Auth::user();
        $objProject = $this->objProjectService->find($project);
        $objOwnAccount = $objUser->userAccounts()->where("soundblock_accounts.account_uuid", $objProject->account_uuid)->first();

        if (is_null($objOwnAccount)) {
            $strSoundGroup = sprintf("App.Soundblock.Project.%s", $project);
            $boolAuthorized = is_authorized($objUser, $strSoundGroup, "App.Soundblock.Project.Report.Usage", "soundblock", true, true);

            if (!$this->objProjectService->checkUserInProject($project, $objUser) || !$boolAuthorized) {
                return $this->apiReject(null, "Forbidden.", 403);
            }
        }

        $arrResponse = [];
        $objAccount = $objProject->account;
        $objActivePlan = $objAccount->activePlan;

        if (is_null($objActivePlan)) {
            return $this->apiReject(null, "Invalid Account.", 400);
        }

        $objPlanType = $objActivePlan->planType;
        $arrResponse["limits"]["diskspace"] = $objPlanType->plan_diskspace * 1024;
        $arrResponse["limits"]["bandwidth"] = $objPlanType->plan_bandwidth * 1024;

        $arrResponse["report"] = $this->objProjectService->buildProjectReport($objProject, $objRequest->input("date_start"),
            $objRequest->input("date_end"));

        return $this->apiReply($arrResponse);
    }

    public function getAccountReport(string $account, ReportRequest $objRequest){
        $objUser = Auth::user();
        $objAccount = $this->objPlanService->find($account);
        $objOwnAccount = $objUser->userAccounts()->where("soundblock_accounts.account_uuid", $account)->first();

        if (is_null($objOwnAccount)) {
            $strSoundGroup = sprintf("App.Soundblock.Account.%s", $account);
            $boolAuthorized = is_authorized($objUser, $strSoundGroup, "App.Soundblock.Account.Report.Usage", "soundblock", true, true);

            if (!$this->objPlanService->checkIsAccountMember($objAccount, $objUser) || !$boolAuthorized) {
                return $this->apiReject(null, "Forbidden.", 403);
            }
        }

        $arrResponse = [];
        $objActivePlan = $objAccount->activePlan;

        if (is_null($objActivePlan)) {
            return $this->apiReject(null, "Invalid Account.", 400);
        }

        $objPlanType = $objActivePlan->planType;

        $arrResponse["limits"]["diskspace"] = $objPlanType->plan_diskspace * 1024;
        $arrResponse["limits"]["bandwidth"] = $objPlanType->plan_bandwidth * 1024;

        $arrResponse["report"] = $this->objPlanService->buildAccountReport($objAccount, $objRequest->input("date_start"),
            $objRequest->input("date_end"));

        return $this->apiReply($arrResponse);
    }

    public function getRevenueReportPayment(PaymentsReportRequest $objRequest){
        $arrResponse = [];
        $arrProjects = [];
        $objUser = Auth::user();
        $objAccount = $this->commonService->find($objRequest->input("account_uuid"));

        if ($objRequest->has("account_uuid") && !$objRequest->has("project_uuid")) {
            $strGroupName = $strGroupName = sprintf("App.Soundblock.Account.%s", $objRequest->input("account_uuid"));

            if (!is_authorized($objUser, $strGroupName, "App.Soundblock.Account.Report.Billing", "soundblock", true, true)) {
                return ($this->apiReject(null, "You don't have required permissions.", Response::HTTP_FORBIDDEN));
            }

            $arrProjects = $objAccount->projects->pluck("project_uuid")->toArray();
            $arrResponse["all_revenue"] = $this->objMusicAccountPayment->buildMusicAccountPaymentReport(
                $objRequest->input("account_uuid"),
                $objRequest->input("date_start"),
                $objRequest->input("date_end"),
                $objRequest->input("platform_uuid")
            );
        } elseif ($objRequest->has("account_uuid") && $objRequest->has("project_uuid")) {
            $objProject = $this->objProjectService->find($objRequest->input("project_uuid"));
            $strGroupName = $strGroupName = sprintf("App.Soundblock.Account.%s", $objProject->account_uuid);

            if (
                $objProject->account_uuid != $objRequest->input("account_uuid") ||
                !is_authorized($objUser, $strGroupName, "App.Soundblock.Account.Report.Billing", "soundblock", true, true)
            ) {
                return ($this->apiReject(null, "You don't have required permissions.", Response::HTTP_FORBIDDEN));
            }

            $arrProjects[] = $objRequest->input("project_uuid");
            $arrResponse["all_revenue"] = $this->objMusicAccountPayment->buildMusicProjectPaymentReport(
                $objRequest->input("project_uuid"),
                $objRequest->input("date_start"),
                $objRequest->input("date_end"),
                $objRequest->input("platform_uuid")
            );
        } else {
            return ($this->apiReject(null, "Request must have account or project uuid.", Response::HTTP_BAD_REQUEST));
        }

        $arrResponse["user_revenue"] = $this->musicUserPaymentService->getUserRevenueByProjects(
            $objUser,
            $arrProjects,
            $objRequest->input("platform_uuid"),
            $objRequest->input("date_start"),
            $objRequest->input("date_end")
        );
        $arrResponse["projects_tracks"] = $this->musicUserPaymentService->getProjectTracksReport(
            $objUser,
            $arrProjects,
            $objRequest->input("platform_uuid"),
            $objRequest->input("date_start"),
            $objRequest->input("date_end")
        );

        $boolFlagAppleMessage = false;
        $strFullReportDate = null;
        $objApplePlatform = $this->platformRepo->findByName("Apple Music");
        if (!$objRequest->exists("platform_uuid") || $objApplePlatform->platform_uuid == $objRequest->input("platform_uuid")) {
            $strFullReportDate = $this->reportRevenueMusicRepo->findPlatformReportLatestDateMyMonth($objApplePlatform->platform_uuid);
            $carbonLatestAppleReportDate = Carbon::createFromFormat("Y-m-d", $strFullReportDate);

            if (
                !$objRequest->exists("date_end") ||
                (
                    $objRequest->input("date_start") <= $carbonLatestAppleReportDate->format("Y-m") &&
                    $objRequest->input("date_end") >= $carbonLatestAppleReportDate->format("Y-m")
                )
            ) {
                $boolFlagAppleMessage = true;
            }
        }

        return (
            $this->apiReply(
                $arrResponse,
                "",
                Response::HTTP_OK,
                ["flag_apple_message" => $boolFlagAppleMessage, "apple_report_date" => $strFullReportDate]
            )
        );
    }

    public function getPlatformReportStatus(Request $objRequest){
        $strYear = $objRequest->input("year", Carbon::now()->year);
        $arrData = $this->reportsService->getPlatformsStatus($strYear);

        return ($this->apiReply($arrData, "", Response::HTTP_OK));
    }
}
