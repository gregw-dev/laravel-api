<?php

namespace App\Console\Commands\Soundblock;


use App\Contracts\Core\Slack as SlackService;
use App\Mail\Soundblock\ChargeAnnualFailedAdminReport;
use Mail;
use Carbon\Carbon;
use App\Facades\Exceptions\Disaster;
use App\Contracts\Soundblock\Accounting\Accounting;
use App\Exceptions\Core\Disaster\PaymentTaskException;
use Illuminate\{Console\Command, Database\Eloquent\Builder};
use App\Models\Soundblock\Accounts\AccountPlan;
use App\Mail\Soundblock\ChargeTransactions as ChargeTransactionsMail;
use App\Services\Office\SupportTicket as SupportTicketService;
use App\Services\Common\App as AppService;

class AccountTransactions extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "charge:transactions";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Charge user";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param AccountPlan $accountPlan
     * @param Accounting $accounting
     * @param SupportTicketService $supportTicketService
     * @param AppService $appService
     * @param SlackService $slackService
     * @return void
     */
    public function handle(AccountPlan $accountPlan, Accounting $accounting, SupportTicketService $supportTicketService,
                           AppService $appService, SlackService $slackService): void {
        $carbonNow = Carbon::now();
        $today = $carbonNow->day;

        if ($carbonNow->daysInMonth < 31 && $today == $carbonNow->endOfMonth()->day) {
            $intDiff = 31 - $carbonNow->daysInMonth;
            $todayPlans = $accountPlan->whereHas("account", function (Builder $query) {
                $query->where("flag_status", "active");
            })->where("flag_active", true)->where(function ($query) use ($today, $intDiff, $carbonNow) {
                $query = $query->whereDay("service_date", $today);
                for ($i = 1; $i <= $intDiff; $i++) {
                    $query = $query->orWhereDay("service_date", $carbonNow->endOfMonth()->day + $i);
                }
            })->get();
        } else {
            $todayPlans = $accountPlan->whereHas("account", function (Builder $query) {
                $query->where("flag_status", "active");
            })->where("flag_active", true)->whereDay("service_date", $today)->get();
        }

        /** @var AccountPlan $plan */
        foreach ($todayPlans as $plan) {
            try {
                [$objTransactions, $arrTransactionsMeta, $boolStatus] = $accounting->makeCharge($plan->account);
            } catch (PaymentTaskException $e) {
                $boolStatus = false;
            }

            $supportUuid = null;

            if (!$boolStatus) {
                $objApp = $appService->findOneByName("soundblock");
                $arrSupportParams = [
                    "support" => "Customer Service",
                    "title" => "Soundblock Charge Failed",
                    "message" => [
                        "text" => "Failed account transactions charge."
                    ]
                ];
                [$objTicket, $objMsg] = $supportTicketService->creteCoreTicket($plan->account->user, $objApp, $arrSupportParams, false);
                $supportUuid = $objTicket->ticket_uuid;

                /* Send Email to Admins */
                $arrMailTo = ["swhite@arena.com"];

                if (config("app.env") === "prod") {
                    $arrMailTo = ["devans@arena.com", "swhite@arena.com"];
                }

                $total = 0.0;
                if (!empty($objTransactions)) {
                    $total = $objTransactions->sum("transaction_amount");
                }

                Mail::to($arrMailTo)->send(new ChargeAnnualFailedAdminReport($plan->account, $objTicket->ticket_uuid, "transactions", $total));

                /* Send Report to Slack */
                if (isset($e)) {
                    $slackService->chargeFailedExceptionReport("transactions", $plan->account->account_name, $plan->account->account_uuid, $plan->planType->plan_name, $e->getMessage());
                } else {
                    $slackService->chargeFailedReport("transactions", $plan->account->account_name, $plan->account->account_uuid, $plan->planType->plan_name);
                }

                Mail::send(new ChargeTransactionsMail($boolStatus, $plan, $objTransactions ?? [], $arrTransactionsMeta ?? [], $supportUuid));
            } else {
                if (!empty($objTransactions)) {
                    Mail::send(new ChargeTransactionsMail($boolStatus, $plan, $objTransactions, $arrTransactionsMeta, $supportUuid));
                }
            }
        }
    }
}
