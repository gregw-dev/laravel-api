<?php

namespace App\Mail\Soundblock;

use App\Models\Core\App;
use App\Models\Soundblock\Accounts\Account as AccountModel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UpcomingAnnualCharge extends Mailable
{
    use Queueable, SerializesModels;

    /** @var AccountModel */
    private AccountModel $objAccount;

    /**
     * Create a new message instance.
     *
     * @param AccountModel $objAccount
     */
    public function __construct(AccountModel $objAccount)
    {
        $this->objAccount = $objAccount;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->from(config("constant.email.soundblock.address"), config("constant.email.soundblock.name"));
        $this->subject($this->objAccount->activePlan->planType->plan_name . " Renewal Notice");
        $this->withSwiftMessage(function ($message) {
            $message->app = App::where("app_name", "soundblock")->first();
        });

        $frontendUrl = app_url("soundblock", "http://localhost:4200") . "account/accounts";

        $accountDetails = [
            "Account Holder" => $this->objAccount->user->name,
            "Account Name"   => $this->objAccount->account_name,
            "Plan" => $this->objAccount->activePlan->planType->plan_name,
            "Annual Charge" => "$" . $this->objAccount->activePlan->planType->plan_rate,
            "Date of Charge" => Carbon::createFromFormat("Y-m-d", $this->objAccount->activePlan->service_date)->format("m/d/y"),
        ];

        return ($this->view("mail.soundblock.upcoming_charge_annual")
            ->with([
                "link" => $frontendUrl,
                "account" => $accountDetails,
                "account_name" => $this->objAccount->account_name,
                "user_name" => $this->objAccount->user->name,
                "plan_type" => $this->objAccount->activePlan->planType->plan_name
            ])
        );
    }
}
