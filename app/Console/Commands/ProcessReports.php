<?php

namespace App\Console\Commands;

use App\Contracts\Core\Slack as SlackService;
use App\Jobs\Soundblock\Reports\FindReportDirectories;
use Illuminate\Console\Command;

class ProcessReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Read All Files from Revenue Reports Filesystem';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(SlackService $slackService)
    {
        parent::__construct();
        $this->slackService=$slackService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (env("APP_ENV") != "develop") {
            dispatch(new FindReportDirectories("reports-ftp"));
        }

        return 1;
    }

}
