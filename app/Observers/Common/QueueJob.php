<?php

namespace App\Observers\Common;

use App\Events\Common\QueueStatus;
use Util;
use App\Contracts\Core\Slack;
use App\Models\Common\QueueJob as QueueJobModel;

class QueueJob {

    /**
     * @param QueueJobModel $objQueueJob
     * @return void
     */
    public function released(QueueJobModel $objQueueJob) {
        $objQueueJob->{QueueJobModel::STOP_AT} = Util::now();
        $objQueueJob->{QueueJobModel::STAMP_STOP} = microtime(true);
        $objQueueJob->flag_status = "Succeeded";
        $objQueueJob->job_seconds = round($objQueueJob->{QueueJobModel::STAMP_STOP} - $objQueueJob->{QueueJobModel::STAMP_START});

        $objQueueJob->save();

        event(new QueueStatus($objQueueJob));

    }

    /**
     * @param QueueJobModel $objQueueJob
     * @return void
     */
    public function failed(QueueJobModel $objQueueJob) {
        $objQueueJob->{QueueJobModel::STOP_AT} = Util::now();
        $objQueueJob->{QueueJobModel::STAMP_STOP} = microtime(true);

        $objSlackService = resolve(Slack::class);
        $jobContent = $objQueueJob->job_json;
        $objSlackService->jobNotification(
            $jobContent["exception"]["message"],
            "notify-urgent",
            $objQueueJob["job_type"] === null ? "null" : $objQueueJob["job_type"],
            $objQueueJob["job_name"] === null ? "null" : $objQueueJob["job_name"],
            $objQueueJob->user
        );

        event(new QueueStatus($objQueueJob));
    }
}
