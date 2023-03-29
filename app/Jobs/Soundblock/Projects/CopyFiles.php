<?php

namespace App\Jobs\Soundblock\Projects;

use Throwable;
use App\Events\Common\PrivateNotification;
use App\Events\Soundblock\OnHistory;
use App\Helpers\Builder;
use App\Jobs\Soundblock\Ledger\CollectionLedger;
use App\Models\Common\QueueJob;
use App\Models\Soundblock\Projects\Project;
use App\Services\Common\QueueJob as QueueJobService;
use App\Services\Soundblock\Collection;
use App\Services\Soundblock\Ledger\CollectionLedger as CollectionLedgerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CopyFiles implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private QueueJob $objQueueJob;
    private Project $objProject;
    private string $strCollectionComment;
    private array $arrFiles;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600;
    private array $arrUserMetaInfo;

    /**
     * Create a new job instance.
     *
     * @param QueueJob $objQueueJob
     * @param Project $objProject
     * @param string $strCollectionComment
     * @param array $arrFiles
     * @param array $arrUserMetaInfo
     */
    public function __construct(QueueJob $objQueueJob, Project $objProject, string $strCollectionComment, array $arrFiles, array $arrUserMetaInfo) {
        $this->objQueueJob = $objQueueJob;
        $this->objProject = $objProject;
        $this->strCollectionComment = $strCollectionComment;
        $this->arrFiles = $arrFiles;
        $this->arrUserMetaInfo = $arrUserMetaInfo;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Collection $objCollectionService, QueueJobService $qjService) {
        echo "Handling time... " . microtime(true) . PHP_EOL;

        if (!$this->job) {
            throw new \Exception("Something went wrong", 417);
        }

        $queueJobParams = [
            "queue_id" => is_null($this->job) ? null : $this->job->getJobId(),
            "job_name" => is_null($this->job) ? null : $this->job->payload()["displayName"],
            "job_json" => ["project" => $this->objProject->project_uuid],
        ];

        $queueJob = $qjService->update($this->objQueueJob, $queueJobParams);

        $objUser = $queueJob->user;

        try {
            \DB::beginTransaction();

            $objCollection = $objCollectionService->createNewCollection($this->objProject, $this->strCollectionComment, $this->arrUserMetaInfo, $objUser);

            $arrHistoryFiles = $objCollectionService->addFiles($objCollection, $this->arrFiles, $this->arrUserMetaInfo, $objUser);

            \DB::commit();
        } catch (\Exception $exception) {
            \DB::rollBack();

            throw $exception;
        }

        event(new OnHistory($objCollection, "Created", $arrHistoryFiles, $objUser));

        dispatch(new CollectionLedger($objCollection, CollectionLedgerService::CREATE_EVENT, $this->arrUserMetaInfo))->onQueue("ledger");

        if ($queueJob->flag_silentalert == 0) {
            $contents = [
                "notification_name"   => "Extract Files",
                "notification_memo"   => "All files are extracted.",
                "notification_action" => Builder::notification_button("OK"),
                "message"             => "All files are extracted and registered successfully.",
            ];

            $flags = [
                "notification_state" => "unread",
                "flag_canarchive"    => true,
                "flag_candelete"     => true,
                "flag_email"         => false,
            ];

            event(new PrivateNotification($objUser, $contents, $flags, $queueJob->app));
        }
    }

    /**
     * @param Throwable $exception
     */
    public function failed(Throwable $exception)
    {
        $this->objQueueJob->update(["flag_status" => "Failed"]);
    }
}
