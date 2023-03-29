<?php

namespace App\Mail\Office;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Withdrawal extends Mailable
{
    use Queueable, SerializesModels;

    private array $arrData;
    private $objUser;

    /**
     * Create a new message instance.
     *
     * @param array $arrData
     * @param $objUser
     */
    public function __construct(array $arrData, $objUser)
    {
        $this->arrData = $arrData;
        $this->objUser = $objUser;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->from(config("constant.email.office.address"), config("constant.email.office.name"));
        $this->subject("Soundblock Withdrawal Request: " . $this->arrData["user"]);
        $frontendUrl = app_url("office", "http://localhost:4200") . "soundblock/payments";

        return $this->view("mail.office.withdrawal")->with([
            "frontendUrl"      => $frontendUrl,
            "userName"         => $this->arrData["user"],
            "officeUserName"   => $this->objUser->name,
            "withdrawalAmount" => number_format($this->arrData["withdrawal_amount"], 2),
        ]);
    }
}
