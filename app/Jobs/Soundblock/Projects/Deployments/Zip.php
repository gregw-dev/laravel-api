<?php

namespace App\Jobs\Soundblock\Projects\Deployments;

use Builder;
use Exception;
use App\Models\Common\QueueJob;
use App\Helpers\Filesystem\Soundblock;
use App\Models\Soundblock\Collections\Collection;
use App\Services\Common\QueueJob as QueueJobService;
use App\Services\Common\Zip as ZipService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Common\App as AppService;
use App\Contracts\Core\Slack as SlackService;

class Zip implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    /**
     * @var Collection
     */
    private Collection $collection;
    /**
     * @var QueueJob
     */
    private QueueJob $queueJob;

    /**
     * Create a new job instance.
     *
     * @param Collection $collection
     * @param QueueJob $queueJob
     */
    public function __construct(Collection $collection, QueueJob $queueJob) {
        $this->collection = $collection;
        $this->queueJob = $queueJob;
    }

    /**
     * Execute the job.
     *
     * @param ZipService $zipService
     * @param QueueJobService $qjService
     * @param AppService $appService
     * @return void
     * @throws Exception
     */
    public function handle(ZipService $zipService, QueueJobService $qjService, AppService $appService) {
        $queueJobParams = [
            "queue_id" => is_null($this->job) ? null : $this->job->getJobId(),
            "job_name" => is_null($this->job) ? null : $this->job->payload()["displayName"],
        ];

        $queueJob = $qjService->update($this->queueJob, $queueJobParams);

        $strPath = $zipService->zipMusic($this->collection);

        if ($strPath) {
            $uploadedTracks = 0;

            foreach ($this->collection->tracks as $key => $objTrack) {
                if (bucket_storage("office")->exists("public/" . Soundblock::deployment_project_track_path($objTrack))) {
                    $uploadedTracks += 1;
                }
            }

            if ($this->collection->tracks->count() != $uploadedTracks) {
                throw new Exception("Some tracks were not uploaded. Uploaded tracks: {$uploadedTracks}/{$this->collection->tracks->count()}");
            }

            $queueJob = $qjService->update($queueJob, [
                "job_json" => [
                    "path" => $strPath,
                ],
            ]);

            $objApp = $appService->findOneByName("office");
            $strNotificationArtistName = isset($this->collection->project->artist) ? "by {$this->collection->project->artist->artist_name}" : "";
            $strMemo = "&quot;{$this->collection->project->project_title}&quot; {$strNotificationArtistName} <br>Soundblock &bull; {$this->collection->project->account->account_name}";

            notify_group_permission("Arena.Support", "Arena.Support.Soundblock", $objApp, "Deployment Download Ready", $strMemo, Builder::notification_link([
                "link_name" => "Check Deployments",
                "url"       => app_url("office") . "soundblock/deployments",
            ]));
        } else {
            $objApp = $appService->findOneByName("office");
            $strMemo = "&quot;{$this->collection->project->project_title}&quot; <br>Soundblock &bull; {$this->queueJob->user->name}";

            notify_group_permission("Arena.Support", "Arena.Support.Soundblock", $objApp, "Deployment Download Failed", $strMemo);

            throw new Exception("ZipMusic did not return correct value.");
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        $intTotalSize = 0;
        $strMessage = $exception->getMessage();
        $objTracks = $this->collection->tracks;

        foreach ($objTracks as $objTrack) {
            $intTotalSize += $objTrack->file->file_size;
        }

        $this->queueJob->update(["flag_status" => "Failed"]);
        $objSlackService = resolve(SlackService::class);
        $objSlackService->jobNotification(
            $strMessage . "; Tracks size - " . $intTotalSize,
            config("slack.channels.exceptions"),
            $this->queueJob->job_type,
            $this->queueJob->job_name,
            $this->queueJob->user
        );
    }
}
