<?php

namespace App\Console\Commands\Soundblock;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\Soundblock\Tracks\Track as TrackModel;
use App\Jobs\Soundblock\Reports\UpdateAggregateTables;
use App\Repositories\Soundblock\ReportRevenueMusic as ReportRevenueMusicRepository;
use App\Models\Soundblock\Reports\RevenueMusicUnmatched as RevenueMusicUnmatchedModel;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessUnmatchedReports extends Command implements ShouldQueue
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "reports:unmatched";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "This command processing unmatched reports records and save matching to corresponding table.";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (env("APP_ENV") != "develop") {
            $objAllTracks = TrackModel::all();
            $arrTracksIds = [];
            $objReportRevenueMusicRepo = resolve(ReportRevenueMusicRepository::class);

            foreach ($objAllTracks as $objCurrTrack) {
                $arrTracksIds[$objCurrTrack->track_id] = strtolower(str_replace("-", "", $objCurrTrack->track_isrc));
            }

            RevenueMusicUnmatchedModel::chunk(5000, function ($objReportsUnmatched) use ($objReportRevenueMusicRepo, $objAllTracks, $arrTracksIds) {
                foreach ($objReportsUnmatched as $objReportUnmatched) {
                    $objTrack = null;
                    $ReportUnmatchedIsrc = strtolower(str_replace("-", "", $objReportUnmatched->track_isrc));
                    $key = array_search($ReportUnmatchedIsrc, $arrTracksIds);
                    if ($key) {
                        $objTrack = $objAllTracks->where("track_id", $key)->first();
                    }

                    if (!empty($objTrack)) {
                        $arrTrack = [
                            "currency" => $objReportUnmatched->report_currency,
                            "quantity" => $objReportUnmatched->report_plays,
                            "revenue" => $objReportUnmatched->report_revenue,
                            "revenue_usd" => $objReportUnmatched->report_revenue_usd,
                        ];

                        $objReportRevenueMusicRepo->storeFromUnmatched(
                            $objTrack,
                            $arrTrack,
                            $objReportUnmatched->platform,
                            $objReportUnmatched->date_starts,
                            $objReportUnmatched->date_ends,
                            $objReportUnmatched->subPlatform ?? null
                        );
                        $objReportUnmatched->delete();
                    }
                }
            });

            $startDate = Carbon::parse("2014-03-01")->format("Y-m-d");
            for ($i = $startDate; $i != Carbon::parse("2022-05-01")->format("Y-m-d"); $i = Carbon::parse($i)->addMonth()->format("Y-m-d")) {
                dispatch(new UpdateAggregateTables(
                    Carbon::parse($i)->startOfMonth()->format("Y-m-d"),
                    Carbon::parse($i)->endOfMonth()->format("Y-m-d")
                ));
            }
        }

        return 0;
    }
}
