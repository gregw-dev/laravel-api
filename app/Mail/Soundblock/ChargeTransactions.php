<?php

namespace App\Mail\Soundblock;

use App\Models\Core\App;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Soundblock\Accounts\AccountPlan as AccountPlanModel;

class ChargeTransactions extends Mailable
{
    use Queueable, SerializesModels;

    private $objTransactions;
    private bool $flagStatus;
    private ?string $uuidTicket;
    /** @var AccountPlanModel */
    private AccountPlanModel $objPlan;
    private ?array $arrTransactionsMeta;

    /**
     * Create a new message instance.
     *
     * @param bool $flagStatus
     * @param AccountPlanModel $objPlan
     * @param array $objTransactions
     * @param array|null $arrTransactionsMeta
     * @param string|null $uuidTicket
     */
    public function __construct(bool $flagStatus, AccountPlanModel $objPlan, $objTransactions = [], ?array $arrTransactionsMeta = [], ?string $uuidTicket = null)
    {
        $this->objTransactions = $objTransactions;
        $this->flagStatus = $flagStatus;
        $this->uuidTicket = $uuidTicket;
        $this->objPlan = $objPlan;
        $this->arrTransactionsMeta = $arrTransactionsMeta;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $total = 0.0;
        $chargeDetails = [];
        $arrChargeDetails = [];
        $objAccount = $this->objPlan->account;

        foreach ($this->objTransactions as $objTransaction) {
            $arrChargeDetails[$objTransaction->transaction_type] =+ $objTransaction->transaction_amount;
        }

        if (array_key_exists("user", $arrChargeDetails) && $arrChargeDetails["user"] > 0) {
            $chargeDetails["Additional users"]["quantity"] = $this->arrTransactionsMeta["users"] > 1 ? $this->arrTransactionsMeta["users"] - 1 : $this->arrTransactionsMeta["users"];
            $chargeDetails["Additional users"]["total"] = number_format($arrChargeDetails["user"], 2);
            $total += $arrChargeDetails["user"];
        }

        if (array_key_exists("diskspace", $arrChargeDetails) && $arrChargeDetails["diskspace"] > 0) {
            $chargeDetails["Additional storage"]["quantity"] = number_format($this->arrTransactionsMeta["diskspace"], 2) . "GB";
            $chargeDetails["Additional storage"]["total"] = number_format($arrChargeDetails["diskspace"], 2);
            $total += $arrChargeDetails["diskspace"];
        }

        if (array_key_exists("bandwidth", $arrChargeDetails) && $arrChargeDetails["bandwidth"] > 0) {
            $chargeDetails["Additional data transfer"]["quantity"] = number_format($this->arrTransactionsMeta["bandwidth"], 2) . "GB";
            $chargeDetails["Additional data transfer"]["total"] = number_format($arrChargeDetails["bandwidth"], 2);
            $total += $arrChargeDetails["bandwidth"];
        }

        if (!$this->flagStatus) {
            $frontendSupportUrl = app_url("soundblock", "http://localhost:4200") . "support?ticket_id=" . $this->uuidTicket;
        } else {
            $frontendSupportUrl = app_url("soundblock", "http://localhost:4200") . "support";
        }

        $frontendAccountLink = app_url("soundblock", "http://localhost:4200") . "account/accounts";

        $accountDetails = [
            "Account Holder" => $objAccount->user->name,
            "Account Name"   => $objAccount->account_name,
            "Plan" => $this->objPlan->planType->plan_name,
            "Date of Charge" => Carbon::createFromFormat("Y-m-d", $objAccount->activePlan->service_date)->format("m/d/y"),
        ];

        $this->to($objAccount->user->primary_email->user_auth_email);
        $this->from(config("constant.email.soundblock.address"), config("constant.email.soundblock.name"));
        $this->withSwiftMessage(function ($message) {
            $message->app = App::where("app_name", "soundblock")->first();
        });
        $strSubject = $this->objPlan->planType->plan_name . " Monthly Payment";
        $this->subject($strSubject);

        return ($this->view("mail.soundblock.charge_transactions")
            ->with([
                "support_link" => $frontendSupportUrl,
                "account_link" => $frontendAccountLink,
                "account" => $accountDetails,
                "charge" => $chargeDetails,
                "status" => $this->flagStatus,
                "subject" => $strSubject,
                "totalAmount" => number_format($total, 2)
            ])
        );
    }
}
