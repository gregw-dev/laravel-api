<?php

namespace App\Console\Commands\Soundblock;

use Mail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use App\Mail\Soundblock\UpcomingAnnualCharge;
use App\Models\Soundblock\Accounts\AccountPlan;

class UpcomingChargeMailing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "upcoming:charge";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Command for notify users about upcoming charge.";


    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param AccountPlan $accountPlanModel
     * @return int
     */
    public function handle(AccountPlan $accountPlanModel)
    {
        $carbonNextWeek = Carbon::now()->addWeek();
        $nextWeekMonth = $carbonNextWeek->month;
        $nextWeekDay = $carbonNextWeek->day;

        $this->annualMailing($accountPlanModel, $nextWeekMonth, $carbonNextWeek, $nextWeekDay);

        return 0;
    }

    private function annualMailing(AccountPlan $accountPlanModel, $nextWeekMonth, $carbonNextWeek, $nextWeekDay){
        if(!$carbonNextWeek->format("L") && $nextWeekMonth == 2 && $nextWeekDay == 28){
            $annualPlans = $accountPlanModel->whereHas("account", function (Builder $query) {
                $query->where("flag_status", "active");
            })
                ->where("flag_active", true)
                ->whereMonth("service_date", $nextWeekMonth)
                ->where(function (Builder $query) use ($nextWeekDay) {
                    $query->whereDay("service_date", $nextWeekDay)->orWhereDay("service_date", "29");
                })
                ->get();
        } else {
            $annualPlans = $accountPlanModel->whereHas("account", function (Builder $query) {
                $query->where("flag_status", "active");
            })
                ->where("flag_active", true)
                ->whereMonth("service_date", $nextWeekMonth)
                ->whereDay("service_date", $nextWeekDay)
                ->get();
        }

        foreach ($annualPlans as $annualPlan) {
            if ($annualPlan->account->activePlan->planType->plan_rate > 0) {
                Mail::to($annualPlan->account->user->primary_email->user_auth_email)->send(new UpcomingAnnualCharge($annualPlan->account));
            }
        }
    }
}
