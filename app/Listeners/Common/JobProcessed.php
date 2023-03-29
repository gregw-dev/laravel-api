<?php

namespace App\Listeners\Common;

use App\Events\Common\QueueStatus;
use App\Services\Common\QueueJob as QueueJobService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Events\JobProcessed as JobProcessedEvent;

class JobProcessed
{
    /** @var QueueJobService */
    private QueueJobService $queueJobService;

    /**
     * Create the event listener.
     *
     * @param QueueJobService $queueJobService
     */
    public function __construct(QueueJobService $queueJobService)
    {
        $this->queueJobService = $queueJobService;
    }

    /**
     * Handle the event.
     *
     * @param  JobProcessedEvent  $event
     * @return void
     */
    public function handle(JobProcessedEvent $event)
    {
        $job = $event->job;
        $queueJob = $this->queueJobService->findByJobId($job->getJobId());

        if ($queueJob) {
            $queueJob->released();
            $arrPendingJobs = $this->queueJobService->getPendingJobsByStatus($queueJob->job_type);

            foreach ($arrPendingJobs as $objJob) {
                event(new QueueStatus($objJob));
            }
        }
    }
}
