<?php

namespace App\Console\Commands\Health;

use App\Contracts\Core\Slack;
use Illuminate\Console\Command;

class Supervisor extends Command {
    /**
     * @const QUEUES
     * This array contains queue worker name and number of workers to run at a time
     * format ["Worker Name" => "Number of Worker"]
     */
    const  QUEUES = [
        "laravel-worker"        => 3,
        "laravel-worker-ledger" => 1,
    ];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health:supervisor';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor Superviord Health';
    /**
     * @var \App\Contracts\Core\Slack
     */
    private Slack $slackService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Slack $slackService) {
        parent::__construct();
        $this->slackService = $slackService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {

        foreach (self::QUEUES as $strWorkerName => $intNumWorker) {

            if ( $this->getNumberOfQueueWorker($strWorkerName) < 1) {
                    $this->startQueueWorker($strWorkerName);
            }
        }

        return 0;
    }

    private function getNumberOfQueueWorker(string $strWorkerName): int
    {
        $intCount=0;
        $arrOutput=null;
        exec("sudo -E /usr/bin/supervisorctl status",$arrOutput);
        \Log::info("Supervisorctl- Queue Worker Status", $arrOutput);
        foreach ($arrOutput as $output){
            if(strpos($output,$strWorkerName.":") === 0){
                $intCount++;
            }
        }
        return $intCount;
    }

    private function startQueueWorker(string $strWorkerName, int $intNumWorker = 1)
    {
        $output = null;
        exec("sudo -E /usr/bin/supervisorctl start $strWorkerName:*",$output);
        \Log::info("Supervisor Start $strWorkerName:*", $output);

        if ( $this->getNumberOfQueueWorker($strWorkerName) < $intNumWorker) {
            $strQueueName= ($strWorkerName == "laravel-worker") ? "default" : substr($strWorkerName, 15);
            $this->slackService->supervisorNotification(ucfirst($strQueueName), "notify-urgent");
        }
    }
}
