<?php

namespace App\Mail\Soundblock;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Soundblock\Accounts\Account as AccountModel;

class ChargeAnnualFailedAdminReport extends Mailable
{
    use Queueable, SerializesModels;

    private string $uuidTicket;
    /** @var AccountModel */
    private AccountModel $objAccount;
    private string $strChargeType;
    private ?float $totalTransactions;

    /**
     * Create a new message instance.
     *
     * @param AccountModel $objAccount
     * @param string $uuidTicket
     * @param string $strChargeType
     * @param float|null $totalTransactions
     */
    public function __construct(AccountModel $objAccount, string $uuidTicket, string $strChargeType, ?float $totalTransactions = null)
    {
        $this->uuidTicket = $uuidTicket;
        $this->objAccount = $objAccount;
        $this->strChargeType = $strChargeType;
        $this->totalTransactions = $totalTransactions;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $frontendUrl = app_url("office", "http://localhost:4200") . "customers/support/tickets/" . $this->uuidTicket;

        $accountDetails = [
            "Account Holder" => $this->objAccount->user->name,
            "Account Name"   => $this->objAccount->account_name,
            "Plan" => $this->objAccount->activePlan->planType->plan_name,
            "Annual Charge" => "$" . $this->objAccount->activePlan->planType->plan_rate,
            "Date of Charge" => Carbon::createFromFormat("Y-m-d", $this->objAccount->activePlan->service_date)->format("m/d/Y"),
            "Customer Email" => $this->objAccount->user->primary_email->user_auth_email,
            "Customer Phone" => $this->objAccount->user->primary_phone->phone_number ?? "undefined"
        ];

        if (!is_null($this->totalTransactions)) {
            unset($accountDetails["Annual Charge"]);
            $accountDetails["Transactions Amount"] = "$" . $this->totalTransactions;
        }

        $this->subject("Soundblock " . ucfirst($this->strChargeType) . " Charge Failed");
        $this->from(config("constant.email.office.address"), config("constant.email.office.name"));

        return ($this->view("mail.soundblock.charge_failed_admin_report")
            ->with([
                "link" => $frontendUrl,
                "account" => $accountDetails,
                "charge_type" => $this->strChargeType
            ])
        );
    }
}
