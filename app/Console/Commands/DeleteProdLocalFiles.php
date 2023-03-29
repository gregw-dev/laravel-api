<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;

class DeleteProdLocalFiles extends Command implements ShouldQueue
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:prod:local:files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600;


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
        $soundblockBucket = bucket_storage("soundblock");
        $strBucketUploadPath = "prod_local_files";

        foreach (Storage::disk("local")->directories("accounts") as $strAccountDirectory) {
            foreach (Storage::disk("local")->directories($strAccountDirectory . "/projects") as $strProjectDir) {
                foreach (Storage::disk("local")->files($strProjectDir . "/files") as $strProjectFilePath) {
                    $soundblockBucket->writeStream(
                        $strBucketUploadPath . "/" . $strProjectFilePath,
                        Storage::disk("local")->readStream($strProjectFilePath)
                    );
                    Storage::disk("local")->delete($strProjectFilePath);
                }
            }
            Storage::disk("local")->deleteDirectory($strAccountDirectory);
        }

        return 1;
    }

}
