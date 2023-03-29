<?php

namespace App\Console;

use DateTimeZone;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\{Apparel\ReplaceProductId,
    Apparel\ScrapingDataRelease,
    Health\LedgerMicroservice,
    LedgerConsole,
    Migration\FreshCommand,
    Soundblock\AccountTransactions,
    User\RemoveNotVerifiedEmails,
    Core\Social\GetInstagramImages
};

class Kernel extends ConsoleKernel {
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        AccountTransactions::class,
        FreshCommand::class,
        LedgerConsole::class,
        RemoveNotVerifiedEmails::class,
        ReplaceProductId::class,
        ScrapingDataRelease::class,
        GetInstagramImages::class,
        LedgerMicroservice::class,
    ];

    /**
     * Define the application"s command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule) {
        $schedule->command("caching")->dailyAt("12:00")->timezone("America/New_York")->before(function () {
             echo "Starting cache job." . PHP_EOL;
        })->after(function () {
            echo "Ending cache job." . PHP_EOL;
        })->appendOutputTo("storage/schedule.log")->onOneServer();

        /*
         * TASK FOR ACCOUNT TRANSACTIONS CHARGES
         * */
//        $schedule->command("charge:transactions")->dailyAt("08:00")->timezone("America/New_York")->before(function () {
//            echo "Starting account plan transactions charge job.\n";
//        })->after(function () {
//            echo "Ending account plan transactions charge job.\n";
//        })->appendOutputTo("storage/schedule.log")->onOneServer();

        /*
         * TASK FOR ACCOUNT PLAN CHARGES
         * */
        $schedule->command("charge:plan")->dailyAt("00:00")->timezone("America/New_York")->before(function () {
            echo "Starting account plan charge job.\n";
        })->after(function () {
            echo "Ending account plan charge job.\n";
        })->appendOutputTo("storage/schedule.log")->onOneServer();

        /*
         * TASK MAILING ABOUT UPCOMING CHARGES
         * */
        $schedule->command("upcoming:charge")->dailyAt("08:00")->timezone("America/New_York")->before(function () {
            echo "Starting upcoming charge mailing job.\n";
        })->after(function () {
            echo "Ending upcoming charge mailing job.\n";
        })->appendOutputTo("storage/schedule.log")->onOneServer();

        /*
         * TASK FOR REMOVING NOT VERIFIED EMAILS
         * */
//        $schedule->command("user:emails:clear")->dailyAt("00:00")->timezone("America/New_York")->before(function () {
//            echo "Starting clearing not verified emails job.\n";
//        })->after(function () {
//            echo "Ending clearing not verified emails job.\n";
//        })->appendOutputTo("storage/schedule.log")->onOneServer();

        /*
         * TASK FOR GETTING IMAGES FROM INSTAGRAM ACCOUNT
         * */
        $schedule->command("social:instagram:images")->dailyAt("00:00")->timezone("America/New_York")
                 ->before(function () {
                     echo "Starting clearing not verified emails job.\n";
                 })->after(function () {
            echo "Ending clearing not verified emails job.\n";
        })->appendOutputTo("storage/schedule.log")->onOneServer();

        /*
         * TELESCOPE DATA PRUNE
         * */
        $schedule->command('telescope:prune')->dailyAt("00:00")->timezone("America/New_York")
           ->onOneServer();

        $schedule->command('health:ledger')->everyTenMinutes()->timezone("America/New_York")->onOneServer();
        $schedule->command('health:supervisor')->everyMinute()->timezone("America/New_York");

        $schedule->command('notification:deployment')->everyFifteenMinutes()->timezone("America/New_York")
            ->onOneServer();

        $schedule->command('deployment:users_mailing')->everyFifteenMinutes()->timezone("America/New_York")
            ->onOneServer();

        $schedule->command('soundblock:report:daily')->everyThreeMinutes()->timezone("America/New_York")
            ->onOneServer();
        $schedule->command('soundblock:report:monthly')->monthlyOn()->timezone("America/New_York")
            ->onOneServer();

        /*
         * TASK FOR UPDATE EXCHANGE RATES
         * */
        $schedule->command("get:current_exchange_rates")->dailyAt("05:00")->timezone("America/New_York")->before(function () {
            echo "Starting update exchange rates job.\n";
        })->after(function () {
            echo "Ending update exchange rates job.\n";
        })->appendOutputTo("storage/schedule.log")->onOneServer();

        /*
         * TASK FOR GET ALL TODAY'S EXCHANGE RATES
         * */
        $schedule->command("apilayer:get:rates")->dailyAt("05:00")->timezone("America/New_York")->before(function () {
            echo "Starting update all exchange rates job.\n";
        })->after(function () {
            echo "Ending update all exchange rates job.\n";
        })->appendOutputTo("storage/schedule.log")->onOneServer();

        //-- Delete Inactive Conference Rooms
        $schedule->command("soundblock:destroy_inactive_conference_rooms")->everyMinute()->timezone("America/New_York")
        ->onOneServer();

        $schedule->command("process-reports")->monthly()->timezone("America/New_York")->before(function (){
            echo "Starting Processing Music Reports.\n";
        })->after(function (){
            echo "Ending Processing Music Reports.\n";
        })->onOneServer();

        $schedule->command("ledger:sync")->dailyAt("05:00")->timezone("America/New_York")->before(function () {
            echo "Starting sync ledger records.\n";
        })->after(function () {
            echo "Ending sync ledger records.\n";
        })->onOneServer();

        $schedule->command("queue:flush")->weeklyOn(7, "4:00")->timezone("America/New_York")->onOneServer();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands() {
        $this->load(__DIR__ . "/Commands");

        require base_path("routes/console.php");
    }

    /**
     * Get the timezone that should be used by default for scheduled
     * @return DateTimeZone|string|null
     */

    protected function scheduleTimezone() {
        return ("America/New_York");
    }
}
