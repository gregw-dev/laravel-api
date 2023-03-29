<?php

namespace App\Console\Commands\Soundblock;

use Mail;
use Carbon\Carbon;
use App\Facades\Exceptions\Disaster;
use App\Contracts\Soundblock\Accounting\Accounting;
use App\Exceptions\Core\Disaster\PaymentTaskException;
use Illuminate\{Console\Command, Database\Eloquent\Builder};
use App\Models\Soundblock\Accounts\AccountPlan;
use App\Repositories\Soundblock\AccountTransaction as AccountTransactionRepository;
use App\Mail\Soundblock\ChargeAnnualFailedAdminReport;
use App\Mail\Soundblock\ChargeAnnual as ChargeAnnualMail;
use App\Services\Office\SupportTicket as SupportTicketService;
use App\Services\Common\App as AppService;
use App\Contracts\Core\Slack as SlackService;

class AccountPlanCharge extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "charge:plan";

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
     * @param AccountTransactionRepository $accountTransactionRepo
     * @param SupportTicketService $supportTicketService
     * @param AppService $appService
     * @param SlackService $slackService
     * @return void
     */
    public function handle(AccountPlan $accountPlan, Accounting $accounting, AccountTransactionRepository $accountTransactionRepo,
                           SupportTicketService $supportTicketService, AppService $appService, SlackService $slackService): void {
        $carbonNow = Carbon::now();
        $currentMonth = $carbonNow->month;
        $currentDay = $carbonNow->day;

        if(!$carbonNow->format("L") && $currentMonth == 2 && $currentDay == 28){
            $todayPlans = $accountPlan->whereHas("account", function (Builder $query) {
                $query->where("flag_status", "active");
            })
                ->where("flag_active", true)
                ->whereMonth("service_date", $currentMonth)
                ->where(function (Builder $query) use ($currentDay) {
                    $query->whereDay("service_date", $currentDay)->orWhereDay("service_date", "29");
                })
                ->get();
        } else {
            $todayPlans = $accountPlan->whereHas("account", function (Builder $query) {
                $query->where("flag_status", "active");
            })
                ->where("flag_active", true)
                ->whereMonth("service_date", $currentMonth)
                ->whereDay("service_date", $currentDay)
                ->get();
        }

        /** @var AccountPlan $plan */
        foreach ($todayPlans as $plan) {
            try {
                if (intval($plan->planType->plan_rate) > 0) {
                    $objAccountTransaction = $accountTransactionRepo->storeTransaction($plan, $plan->planType->plan_name, $plan->planType->plan_rate, "not paid");
                    $successPayed = $accounting->accountPlanCharge($plan->account, $objAccountTransaction, $plan->planType->plan_rate);
                } else {
                    continue;
                }
            } catch (PaymentTaskException $e) {
                $successPayed = false;
            }

            if (!$successPayed) {
                /* Create support ticket */
                $objApp = $appService->findOneByName("soundblock");
                $arrSupportParams = [
                    "support" => "Customer Service",
                    "title" => "Soundblock Charge Failed",
                    "message" => [
                        "text" => "Failed account plan annual charge."
                    ]
                ];
                [$objTicket, $objMsg] = $supportTicketService->creteCoreTicket($plan->account->user, $objApp, $arrSupportParams, false);

                /* Send Email to Admins */
                $arrMailTo = ["swhite@arena.com"];

                if (config("app.env") === "prod") {
                    $arrMailTo = ["devans@arena.com", "swhite@arena.com"];
                }

                Mail::to($arrMailTo)->send(new ChargeAnnualFailedAdminReport($plan->account, $objTicket->ticket_uuid, "annual"));

                /* Send Report to Slack */
                if (isset($e)) {
                    $slackService->chargeFailedExceptionReport("annual", $plan->account->account_name, $plan->account->account_uuid, $plan->planType->plan_name, $e->getMessage());
                } else {
                    $slackService->chargeFailedReport("annual", $plan->account->account_name, $plan->account->account_uuid, $plan->planType->plan_name);
                }
            }

            /* Send email to user */
            Mail::send(new ChargeAnnualMail($successPayed, $plan->account, $objAccountTransaction, isset($objTicket) ? $objTicket->ticket_uuid : null));
        }
    }
}
