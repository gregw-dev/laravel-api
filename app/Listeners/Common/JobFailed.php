<?php

namespace App\Listeners\Common;

use App\Contracts\Core\Slack;
use Log;
use Throwable;
use App\Models\Common\QueueJob;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobFailed as JobFailedEvent;
use App\Services\Common\QueueJob as QueueJobService;

class JobFailed {
    /** @var QueueJobService */
    protected QueueJobService $queueJobService;

    /**
     * Create the event listener.
     * @param QueueJobService $queueJobService
     *
     */
    public function __construct(QueueJobService $queueJobService) {
        $this->queueJobService = $queueJobService;
    }

    /**
     * Handle the event.
     *
     * @param JobFailedEvent $event
     *
     * @return void
     * @throws \Exception
     */
    public function handle(JobFailedEvent $event) {
        /** @var string */
        $connectionName = $event->connectionName;
        /** @var Throwable */
        $exception = $event->exception;
        $job = $event->job;
        $this->storeException($connectionName, $job, $exception);
    }

    /**
     * @param string $connectionName
     * @param Job $job
     * @param Throwable $exception
     * @return null
     */
    private function storeException(string $connectionName, Job $job, Throwable $exception) {
        Log::info($exception->getMessage());

        /** @var QueueJob */
        $queueJob = $this->queueJobService->findByJobId($job->getJobId(), false);

        if ($queueJob) {
            Log::info("Job Exception Occured", [$queueJob]);

            $exceptionContents = [
                "connection_name" => $connectionName,
                "exception"       => [
                    "class"     => get_class($exception),
                    "code"      => $exception->getCode(),
                    "message"   => $exception->getMessage(),
                    "throwable" => $exception,
                    "trace"     => $exception->getTraceAsString(),
                ],
            ];

            $queueJob = $this->queueJobService->update($queueJob, ["job_json" => $exceptionContents, "flag_status" => "Failed"]);
            $queueJob->failed();
        } else {
//            $objSlackService = resolve(Slack::class);
//            $objSlackService->jobNotification(
//                $exception->getMessage(),
//                "notify-urgent",
//                $job->payload()["displayName"],
//                $job->payload()["displayName"],
//                null
//            );
        }

        return null;
    }
}
