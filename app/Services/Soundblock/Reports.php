<?php

namespace App\Services\Soundblock;

use Carbon\Carbon;
use App\Repositories\Common\Account;
use App\Models\Users\User as UserModel;
use App\Support\Soundblock\MusicReports;
use App\Contracts\Core\Slack as SlackService;
use App\Jobs\Soundblock\Reports\UsersMailing;
use App\Jobs\Soundblock\Reports\UpdateAggregateTables;
use App\Services\Soundblock\Project as ProjectService;
use App\Support\Soundblock\Reports as ReportsSupport;
use App\Repositories\Soundblock\Project as ProjectRepository;
use App\Support\Soundblock\MusicReports as MusicReportsSupport;
use App\Repositories\Soundblock\Platform as PlatformRepository;
use App\Repositories\Soundblock\ReportRevenueMusic as ReportProjectRepository;
use App\Repositories\Soundblock\Data\PlatformReport as PlatformReportRepository;
use App\Repositories\Soundblock\ReportRevenueMusic as ReportRevenueMusicRepository;
use App\Repositories\Soundblock\Payments\MusicUserPayment as MusicUserPaymentRepository;
use App\Repositories\Soundblock\Payments\MusicProjectPayment as MusicProjectPaymentRepository;
use App\Repositories\Soundblock\Payments\MusicAccountPayment as MusicAccountPaymentRepository;

class Reports
{
    /** @var ReportsSupport */
    private ReportsSupport $reportsSupport;
    /** @var ReportProjectRepository */
    private ReportProjectRepository $reportProjectRepo;
    /** @var PlatformRepository */
    private PlatformRepository $platformRepo;
    /** @var MusicUserPaymentRepository */
    private MusicUserPaymentRepository $reportProjectUserRepo;
    /** @var ProjectRepository */
    private ProjectRepository $projectRepo;
    /** @var SlackService */
    private SlackService $slackService;
    private MusicAccountPaymentRepository $musicAccountPaymentRepo;
    private MusicProjectPaymentRepository $musicProjectPaymentRepo;
    private Account $accountRepo;
    private MusicReports $musicReportsSupport;
    /** @var Project */
    private Project $projectService;
    /** @var ReportRevenueMusicRepository */
    private ReportRevenueMusicRepository $reportRevenueMusicRepo;

    /**
     * Report constructor.
     * @param ReportsSupport $reportsSupport
     * @param ReportProjectRepository $reportProjectRepo
     * @param PlatformRepository $platformRepo
     * @param MusicUserPaymentRepository $reportProjectUserRepo
     * @param ProjectRepository $projectRepo
     * @param SlackService $slackService
     * @param MusicAccountPaymentRepository $musicAccountPaymentRepo
     * @param MusicProjectPaymentRepository $musicProjectPaymentRepo
     * @param Account $accountRepo
     * @param MusicReports $musicReports
     * @param Project $projectService
     * @param ReportRevenueMusicRepository $reportRevenueMusicRepo
     */
    public function __construct(ReportsSupport $reportsSupport, ReportProjectRepository $reportProjectRepo,
                                PlatformRepository $platformRepo, MusicUserPaymentRepository $reportProjectUserRepo,
                                ProjectRepository $projectRepo, SlackService $slackService,
                                MusicAccountPaymentRepository $musicAccountPaymentRepo,
                                MusicProjectPaymentRepository $musicProjectPaymentRepo, Account $accountRepo,
                                MusicReports $musicReports, ProjectService $projectService,
                                ReportRevenueMusicRepository $reportRevenueMusicRepo){
        $this->projectRepo              = $projectRepo;
        $this->slackService             = $slackService;
        $this->platformRepo             = $platformRepo;
        $this->reportsSupport           = $reportsSupport;
        $this->reportProjectRepo        = $reportProjectRepo;
        $this->reportProjectUserRepo    = $reportProjectUserRepo;
        $this->musicProjectPaymentRepo  = $musicProjectPaymentRepo;
        $this->musicAccountPaymentRepo  = $musicAccountPaymentRepo;
        $this->accountRepo              = $accountRepo;
        $this->musicReportsSupport      = $musicReports;
        $this->projectService           = $projectService;
        $this->reportRevenueMusicRepo   = $reportRevenueMusicRepo;
    }

    public function getAvailableUsersProjects(UserModel $objUser){
        $arrResponse = [];
        $objAccountsProjects = $this->projectService->findAllByUserWithAccounts($objUser);

        foreach ($objAccountsProjects as $key => $objAccount) {
            $strGroupName = $strGroupName = sprintf("App.Soundblock.Account.%s", $objAccount->account_uuid);

            if (!is_authorized($objUser, $strGroupName, "App.Soundblock.Account.Report.Billing", "soundblock", true, true)) {
                unset($objAccountsProjects[$key]);
            }
        }

        foreach ($objAccountsProjects as $objAccount) {
            foreach ($objAccount["projects"] as $objProject) {
                $boolResult = $this->reportRevenueMusicRepo->checkDataByProject($objProject->project_uuid);

                if ($boolResult) {
                    $objReports = $this->reportRevenueMusicRepo->getAvailableProjectPlatforms($objProject->project_uuid);
                    $arrResponse[$objAccount->account_uuid][$objProject->project_uuid] = array_values(array_unique($objReports->pluck("platform_uuid")->toArray()));
                }
            }
        }

        return ($arrResponse);
    }

    public function getAvailableUsersProjectsforUsage(UserModel $objUser){
        $arrResponse = [];
        $objAccountsProjects = $this->projectService->findAllByUserWithAccounts($objUser);

        foreach ($objAccountsProjects as $objAccount) {
            $arrProjects = [];
            $boolPermission = false;
            $strGroupName = $strGroupName = sprintf("App.Soundblock.Account.%s", $objAccount->account_uuid);

            if (is_authorized($objUser, $strGroupName, "App.Soundblock.Account.Report.Usage", "soundblock", true, true)) {
                $boolPermission = true;
            }

            foreach ($objAccount["projects"] as $objProject) {
                $boolPermission = false;
                $strGroupName = $strGroupName = sprintf("App.Soundblock.Project.%s", $objProject->project_uuid);

                if (is_authorized($objUser, $strGroupName, "App.Soundblock.Project.Report.Usage", "soundblock", true, true)) {
                    $boolPermission = true;
                }

                $arrProjects[] = [
                    "project_uuid" => $objProject->project_uuid,
                    "project_title" => $objProject->project_title,
                    "access" => $boolPermission
                ];
            }

            $arrResponse[] = [
                "account_uuid" => $objAccount->account_uuid,
                "account_name" => $objAccount->account_name,
                "access" => $boolPermission,
                "projects" => $arrProjects
            ];
        }

        return ($arrResponse);
    }

    public function getPlatformsStatus(string $strYear){
        $arrResponse = [];
        $objPlatformReportRepo = resolve(PlatformReportRepository::class);
        $objMusicReportsSupport = resolve(MusicReportsSupport::class);
        $objPlatforms = $this->platformRepo->findAll(null, "music");
        $objPlatforms = $objPlatforms->sortBy("name");
        $objPlatformReports = $objPlatformReportRepo->findReportsByYear($strYear);
        $objPlatformReports = $objPlatformReports->groupBy("report_month");

        foreach ($objPlatforms as $objPlatform) {
            for ($i = 1; $i <= 12; $i++) {
                $month = strlen(strval($i)) == 1 ? "0" . strval($i) : strval($i);
                if ($objPlatformReports->has($month)) {
                    $objPlatformReport = $objPlatformReports[$month]->where("platform_uuid", $objPlatform->platform_uuid)->first();

                    if ($objPlatformReport) {
                        $arrResponse[$objPlatform->name][$month] = Carbon::parse($objPlatformReport->stamp_created_at)->format("m/d/y");
                    } else {
                        $arrResponse[$objPlatform->name][$month] = false;
                    }
                } else {
                    $arrResponse[$objPlatform->name][$month] = false;
                }
            }
        }

        return ($arrResponse);
    }

    /**
     * @param string $strLocalFilePath
     * @param string $strPlatform
     * @param string $strFilePath
     * @return array
     * @throws \Exception
     */
    public function storeFromFilePath(string $strLocalFilePath, string $strPlatform, string $strFilePath): array{
        $objPlatform = $this->platformRepo->findByName($strPlatform);

        if (!$objPlatform) {
            $this->slackService->reportPlatformNotification(
                "Could not read file, `{$strPlatform}`is missing in the database.",
                config("slack.channels.exceptions"),
                "Soundblock Reports Service",
                $strFilePath
            );
            throw (new \Exception($strPlatform . " platform is missing in the database.", 400));
        }

        [$dateStarts, $dateEnds, $insertData] = $this->musicReportsSupport->readFile($strLocalFilePath, $strPlatform, $strFilePath);
        [$arrReportSlackData, $arrReportsMeta] = $this->reportProjectRepo->store($insertData, $objPlatform, $dateStarts, $dateEnds);

        $this->storeMusicUserPayments($dateStarts, $dateEnds);
        $this->storeMusicAccountPayments($dateStarts, $dateEnds);
        $this->storeMusicProjectPayments($dateStarts, $dateEnds);
        dispatch(new UsersMailing())->delay(now()->addMinutes(15));

        return ([true, $arrReportSlackData, $dateStarts]);
    }

    /**
     * @param string $dateStarts
     * @param string $dateEnds
     * @return bool
     * @throws \Exception
     */
    public function storeMusicUserPayments(string $dateStarts, string $dateEnds): bool{
        $arrProjects = $this->reportProjectRepo->findProjectsByDate($dateStarts, $dateEnds);

        foreach($arrProjects as $projectId) {
            $objProject = $this->projectRepo->find($projectId);
            $objContract = $objProject->contracts()->where("flag_status", "Active")->orderBy("contract_version", "desc")->first();

            if ($objContract){
                $this->reportProjectUserRepo->storeUserRevenueByProjectAndDates($objProject, $dateStarts, $dateEnds, $objContract);
            }
        }

        return (true);
    }

    public function storeMusicAccountPayments(string $strDateStarts, string $strDateEnds): bool
    {
        $arrMusicUserPaymentsData = [];
        $objMusicUserReports = $this->reportProjectRepo->findAllBetweenDates($strDateStarts, $strDateEnds);
        $objAppleMusicPlatform = $this->platformRepo->findByName("Apple Music");

        foreach ($objMusicUserReports->groupBy("platform_id") as $intPlatformId => $objReportsByPlatforms) {
            $strPaymentMemo = "";
            if ($objAppleMusicPlatform->platform_id == $intPlatformId) {
                $strPaymentMemo = implode(", ", array_unique($objReportsByPlatforms->pluck("payment_memo")->toArray()));
            }
            $strPlatformUuid = $objReportsByPlatforms->first()->platform_uuid;
            $objReportsGroupedByAccounts = $objReportsByPlatforms->groupBy(function ($objItem){
                return ($objItem->project->account_uuid);
            });

            foreach ($objReportsGroupedByAccounts as $strAccountUuid => $objPayments) {
                $objAccount = $this->accountRepo->find($strAccountUuid);
                $arrMusicUserPaymentsData[] = [
                  "date_starts"    => $strDateStarts,
                  "date_ends"      => $strDateEnds,
                  "platform_id"    => $intPlatformId,
                  "platform_uuid"  => $strPlatformUuid,
                  "account_id"     => $objAccount->account_id,
                  "account_uuid"   => $objAccount->account_uuid,
                  "payment_amount" => $objPayments->sum("report_revenue_usd"),
                  "payment_memo"   => $strPaymentMemo,
                ];
            }
        }

        $this->musicAccountPaymentRepo->storeOrUpdate($arrMusicUserPaymentsData);

        return (true);
    }

    public function storeMusicProjectPayments(string $strDateStarts, string $strDateEnds): bool
    {
        $arrMusicProjectReportsData = [];
        $objAppleMusicPlatform = $this->platformRepo->findByName("Apple Music");
        $objMusicUserReports = $this->reportProjectRepo->findAllBetweenDates($strDateStarts, $strDateEnds);

        foreach ($objMusicUserReports->groupBy("platform_id") as $intPlatformId => $objReportsByPlatforms) {
            $strPaymentMemo = "";
            if ($objAppleMusicPlatform->platform_id == $intPlatformId) {
                $strPaymentMemo = implode(", ", array_unique($objReportsByPlatforms->pluck("payment_memo")->toArray()));
            }
            foreach ($objReportsByPlatforms->groupby("project_id") as $strProjectId => $objPayments) {
                $arrMusicProjectReportsData [] = [
                  "date_starts"        =>   $strDateStarts,
                  "date_ends"          =>   $strDateEnds,
                  "platform_id"        =>   $objPayments->first()->platform_id,
                  "platform_uuid"      =>   $objPayments->first()->platform_uuid,
                  "project_id"         =>   $objPayments->first()->project_id,
                  "project_uuid"       =>   $objPayments->first()->project_uuid,
                  "payment_amount"     =>   $objPayments->sum("report_revenue_usd"),
                  "payment_memo"       =>   $strPaymentMemo,
                ];
            }
        }

        $this->musicProjectPaymentRepo->storeOrUpdate($arrMusicProjectReportsData);

        return (true);
    }
}
