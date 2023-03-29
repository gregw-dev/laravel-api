<?php

namespace App\Jobs\Soundblock\Reports;

use App\Contracts\Core\Slack as SlackService;
use App\Services\Soundblock\Reports as ReportsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateAggregateTables implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private string $dateStarts;
    private string $dateEnds;

    /**
     * Create a new job instance.
     *
     * @param $dateStarts
     * @param $dateEnds
     */
    public function __construct(string $dateStarts, string $dateEnds)
    {
        $this->dateStarts = $dateStarts;
        $this->dateEnds = $dateEnds;
    }

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 3600;

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return md5(implode(",", [$this->dateStarts, $this->dateEnds]));
    }

    /**
     * Execute the job.
     *
     * @param ReportsService $objReportService
     * @return void
     * @throws \Exception
     */
    public function handle(ReportsService $objReportService)
    {
        $objReportService->storeMusicUserPayments($this->dateStarts, $this->dateEnds);
        $objReportService->storeMusicAccountPayments($this->dateStarts, $this->dateEnds);
        $objReportService->storeMusicProjectPayments($this->dateStarts, $this->dateEnds);

        dispatch(new UsersMailing())->delay(now()->addMinutes(15));
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        $objSlackService = resolve(SlackService::class);
        $objSlackService->reportPlatformNotification(
            $exception->getMessage(),
            config("slack.channels.exceptions"),
            "UpdateAggregateTables"
        );
    }
}
