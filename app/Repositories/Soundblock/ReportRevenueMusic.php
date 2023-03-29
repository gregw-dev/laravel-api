<?php

namespace App\Repositories\Soundblock;

use DB;
use App\Repositories\BaseRepository;
use App\Models\Soundblock\Platform as PlatformModel;
use App\Repositories\Soundblock\File as FileRepository;
use App\Models\Soundblock\Reports\RevenueMusic as ReportRevenueModel;
use App\Repositories\Soundblock\Data\ExchangeRates as ExchangeRatesRepository;
use App\Repositories\Soundblock\ReportRevenueMusicUnmatched as ReportRevenueMusicUnmatchedRepository;
use Carbon\Carbon;

class ReportRevenueMusic extends BaseRepository
{
    /** @var File */
    private File $fileRepo;
    /** @var ExchangeRatesRepository */
    private ExchangeRatesRepository $exchangeRatesRepo;
    /** @var ReportRevenueMusicUnmatched */
    private ReportRevenueMusicUnmatched $reportRevenueMusicUnmatchedRepo;

    /**
     * ReportRevenueMusic constructor.
     * @param ReportRevenueModel $objReportRevenue
     * @param File $fileRepo
     * @param ExchangeRatesRepository $exchangeRatesRepo
     * @param ReportRevenueMusicUnmatched $reportRevenueMusicUnmatchedRepo
     */
    public function __construct(ReportRevenueModel $objReportRevenue, FileRepository $fileRepo, ExchangeRatesRepository $exchangeRatesRepo,
                                ReportRevenueMusicUnmatchedRepository $reportRevenueMusicUnmatchedRepo)
    {
        $this->model = $objReportRevenue;
        $this->fileRepo = $fileRepo;
        $this->exchangeRatesRepo = $exchangeRatesRepo;
        $this->reportRevenueMusicUnmatchedRepo = $reportRevenueMusicUnmatchedRepo;
    }

    public function findAllBetweenDates(string $dateStarts, string $dateEnds){
        return ($this->model->where("date_starts", $dateStarts)->where("date_ends", $dateEnds)->get());

    }

    /**
     * @param string $dateStarts
     * @param string $dateEnds
     * @return mixed
     */
    public function findProjectsByDate(string $dateStarts, string $dateEnds)
    {
        return ($this->model->where("date_starts", $dateStarts)->where("date_ends", $dateEnds)->groupBy("project_id")->pluck("project_id"));
    }

    /**
     * @param string $projectUuid
     * @param string $dateStarts
     * @param string $dateEnds
     * @return mixed
     */
    public function findProjectsByProjectAndDate(string $projectUuid, string $dateStarts, string $dateEnds)
    {
        return (
            $this->model->where("date_starts", $dateStarts)
                ->where("date_ends", $dateEnds)
                ->where("project_uuid", $projectUuid)
                ->get()
        );
    }

    public function findAllByProjectsAndDates(array $arrProjectsUuids, string $strPlatformUuid = null, string $dateStarts = null, string $dateEnds = null){
        $query = $this->model->whereIn("project_uuid", $arrProjectsUuids);

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

    public function findPlatformReportLatestDateMyMonth(string $strPlatformUuid){
        $strDateEnds = $this->model->where("platform_uuid", $strPlatformUuid)
            ->orderBy("date_ends", "desc")
            ->first();

        return ($strDateEnds->date_ends);
    }

    public function checkDataByProject(string $strProjectUuid){
        return ($this->model->where("project_uuid", $strProjectUuid)->exists());
    }

    public function getAvailableProjectPlatforms(string $strProjectUuid){
        return ($this->model->where("project_uuid", $strProjectUuid)->select("platform_uuid")->get());
    }

    /**
     * @param array $insertData
     * @param PlatformModel $objPlatform
     * @param string $dateStarts
     * @param string $dateEnds
     * @param string $strPaymentMemo
     * @return array
     * @throws \Throwable
     */
    public function store(array $insertData, PlatformModel $objPlatform, string $dateStarts, string $dateEnds, string $strPaymentMemo = ""): array
    {
        try {
            DB::beginTransaction();

            $objSubPlatform = null;
            if (in_array($objPlatform->name, ["Triller", "Your Band Website", "Snapchat", "Roxi", "Trebel"])) {
                $objSubPlatform = $objPlatform;
                $objPlatform = PlatformModel::where("name", "7 Digital (UK)")->first();
            }
            $arrResponse = [];
            $arrReportsMeta = [
                "matched" => [
                    "count" => 0,
                    "revenue_usd" => 0.0
                ],
                "unmatched" => [
                    "count" => 0,
                    "revenue_usd" => 0.0
                ],
            ];

            foreach ($insertData as $strIsrc => $arrTrackMeta) {
                $strIsrc  = $this->formatIsrcCode($strIsrc);
                $objTrack = $this->fileRepo->findTrackByISRCAdvanced($strIsrc);

                foreach ($arrTrackMeta as $strCurr => $arrTrack) {
                    if ($strCurr != "USD") {
                        if (!array_key_exists("revenue_usd", $arrTrack)) {
                            $arrTrack["revenue_usd"] = $this->convertToUSD($strCurr, $arrTrack["revenue"], $dateStarts, $dateEnds);
                        }
                    }else{
                        $arrTrack["revenue_usd"] = $arrTrack["revenue"];
                    }

                    $arrTrack["currency"] = $strCurr;

                    /* Calculate Merlin Fee */
                    $arrTrack["revenue"] = $arrTrack["revenue"] * ((100 - floatval(env("REPORTS_MERLIN_FEE", 2.9))) / 100);
                    $arrTrack["revenue_usd"] = $arrTrack["revenue_usd"] * ((100 - floatval(env("REPORTS_MERLIN_FEE", 2.9))) / 100);

                    if ($arrTrack["revenue_usd"] > 0 || $arrTrack["quantity"] > 0) {
                        if (!empty($objTrack)) {
                            $objProject = $objTrack->collections()->first()->project;
                            isset($arrResponse[$objProject->project_id]) ? $arrResponse[$objProject->project_id] += $arrTrack["revenue_usd"] : $arrResponse[$objProject->project_id] = $arrTrack["revenue_usd"];
                            $arrReportsMeta["matched"]["count"] += 1;
                            $arrReportsMeta["matched"]["revenue_usd"] += $arrTrack["revenue_usd"];

                            $this->storeMatched($objTrack, $arrTrack, $objPlatform, $dateStarts, $dateEnds, $objSubPlatform, $strPaymentMemo);
                        } else {
                            $arrReportsMeta["unmatched"]["count"] += 1;
                            $arrReportsMeta["unmatched"]["revenue_usd"] += $arrTrack["revenue_usd"];

                            $this->storeUnmatched($arrTrack, $objPlatform, $strIsrc, $dateStarts, $dateEnds, $objSubPlatform, $strPaymentMemo);
                        }
                    }
                }
            }

            DB::commit();

            return ([$arrResponse, $arrReportsMeta]);
        } catch (\Exception $exception) {
            DB::rollBack();

            throw $exception;
        }
    }

    private function formatIsrcCode($strIsrc): string
    {
        return implode("-", [
            substr($strIsrc, 0, 2),
            substr($strIsrc, 2, 3),
            substr($strIsrc, 5, 2),
            substr($strIsrc, 7),
        ]);
    }

    /**
     * @param $objTrack
     * @param $arrTrack
     * @param PlatformModel $objPlatform
     * @param string $dateStarts
     * @param string $dateEnds
     * @param PlatformModel|null $objSubPlatform
     * @param string $strPaymentMemo
     * @return void
     */
    private function storeMatched($objTrack, $arrTrack, PlatformModel $objPlatform, string $dateStarts, string $dateEnds, $objSubPlatform = null, string $strPaymentMemo = ""): void
    {
        $objCollections = $objTrack->collections;

        $this->create( [
            "project_id" => $objCollections[0]->project_id,
            "project_uuid" => $objCollections[0]->project_uuid,
            "track_id" => $objTrack->track_id,
            "track_uuid" => $objTrack->track_uuid,
            "platform_id" => $objPlatform->platform_id,
            "platform_uuid" => $objPlatform->platform_uuid,
            "sub_platform_id" => $objSubPlatform ? $objSubPlatform->platform_id : null,
            "sub_platform_uuid" => $objSubPlatform ? $objSubPlatform->platform_uuid : null,
            "date_starts" => $dateStarts,
            "date_ends" => $dateEnds,
            "report_currency" => $arrTrack["currency"],
            "report_plays" => $arrTrack["quantity"],
            "report_revenue" => $arrTrack["revenue"],
            "report_revenue_usd" => $arrTrack["revenue_usd"],
            "payment_memo" => $strPaymentMemo
        ]);
    }

    /**
     * @param array $arrTrackData
     * @param PlatformModel $objPlatform
     * @param string $strIsrc
     * @param string $dateStarts
     * @param string $dateEnds
     * @param PlatformModel|null $objSubPlatform
     * @param string $strPaymentMemo
     */
    private function storeUnmatched(array $arrTrackData, PlatformModel $objPlatform, string $strIsrc, string $dateStarts, string $dateEnds, $objSubPlatform = null, string $strPaymentMemo = ""): void
    {
        $this->reportRevenueMusicUnmatchedRepo->create([
            "platform_id" => $objPlatform->platform_id,
            "platform_uuid" => $objPlatform->platform_uuid,
            "sub_platform_id" => $objSubPlatform ? $objSubPlatform->platform_id : null,
            "sub_platform_uuid" => $objSubPlatform ? $objSubPlatform->platform_uuid : null,
            "project_name" => $arrTrackData["project"],
            "artist_name" => $arrTrackData["artist"],
            "track_name" => $arrTrackData["track"],
            "track_isrc" => $strIsrc,
            "project_upc" => $arrTrackData["upc"],
            "date_starts" => $dateStarts,
            "date_ends" => $dateEnds,
            "report_currency" => $arrTrackData["currency"],
            "report_plays" => $arrTrackData["quantity"],
            "report_revenue" => $arrTrackData["revenue"],
            "report_revenue_usd" => $arrTrackData["revenue_usd"],
            "payment_memo" => $strPaymentMemo
        ]);
    }


    /**
     * @param $curr
     * @param $revenue
     * @param $date_starts
     * @param $date_ends
     * @return float|int
     */
    private function convertToUSD($curr, $revenue, $date_starts, $date_ends)
    {
        $curr = strtolower($curr);
        $floatRate = \Cache::remember("conversion-rate-" . $curr, 120, fn() => $this->exchangeRatesRepo->findAvgByCodeAndDates($curr, $date_starts, $date_ends));
        return floatval($revenue) / floatval($floatRate);
    }

    public function storeFromUnmatched($objTrack, $arrTrack, PlatformModel $objPlatform, string $dateStarts, string $dateEnds, PlatformModel $objSubPlatform = null): void {
        $this->storeMatched($objTrack, $arrTrack, $objPlatform, $dateStarts, $dateEnds, $objSubPlatform);
    }
}
