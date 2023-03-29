<?php

namespace App\Mail\Soundblock;

use App\Models\Core\App;
use App\Models\Soundblock\Accounts\AccountTransaction as AccountTransactionModel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Soundblock\Accounts\Account as AccountModel;

class ChargeAnnual extends Mailable
{
    use Queueable, SerializesModels;

    private ?string $uuidTicket;
    /** @var AccountModel */
    private AccountModel $objAccount;
    private bool $boolStatus;
    private ?AccountTransactionModel $objTransaction;

    /**
     * Create a new message instance.
     *
     * @param bool $boolStatus
     * @param AccountModel $objAccount
     * @param AccountTransactionModel|null $objTransaction
     * @param string|null $uuidTicket
     */
    public function __construct(bool $boolStatus, AccountModel $objAccount, ?AccountTransactionModel $objTransaction = null, ?string $uuidTicket = null)
    {
        $this->objAccount = $objAccount;
        $this->boolStatus = $boolStatus;
        $this->uuidTicket = $uuidTicket;
        $this->objTransaction = $objTransaction;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if (!$this->boolStatus) {
            $frontendSupportUrl = app_url("soundblock", "http://localhost:4200") . "support?ticket_id=" . $this->uuidTicket;
        } else {
            $frontendSupportUrl = app_url("soundblock", "http://localhost:4200") . "support";
        }

        $frontendAccountLink = app_url("soundblock", "http://localhost:4200") . "account/accounts";

        $accountDetails = [
            "Account Holder" => $this->objAccount->user->name,
            "Account Name"   => $this->objAccount->account_name,
            "Plan" => $this->objAccount->activePlan->planType->plan_name,
            "Annual Charge" => "$" . $this->objAccount->activePlan->planType->plan_rate,
            "Date of Charge" => Carbon::createFromFormat("Y-m-d", $this->objAccount->activePlan->service_date)->format("m/d/Y"),
        ];

        $strSubject = $this->objAccount->activePlan->planType->plan_name . " Payment";

        if ($this->boolStatus) {
            $accountDetails["Transaction ID"] = $this->objTransaction->invoice->charge_id ?? "undefined";
            $strSubject .= " Receipt";
        } else {
            $strSubject .= " Declined";
        }

        $this->to($this->objAccount->user->primary_email->user_auth_email);
        $this->from(config("constant.email.soundblock.address"), config("constant.email.soundblock.name"));
        $this->withSwiftMessage(function ($message) {
            $message->app = App::where("app_name", "soundblock")->first();
        });
        $this->subject($strSubject);

        return ($this->view("mail.soundblock.charge_annual")
            ->with([
                "support_link" => $frontendSupportUrl,
                "account_link" => $frontendAccountLink,
                "account" => $accountDetails,
                "status" => $this->boolStatus,
                "subject" => $strSubject
            ])
        );
    }
}
