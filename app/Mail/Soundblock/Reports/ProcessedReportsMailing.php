<?php

namespace App\Mail\Soundblock\Reports;

use App\Models\Core\App;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Users\User as UserModel;
use App\Models\Soundblock\Platform as PlatformModel;

class ProcessedReportsMailing extends Mailable
{
    use Queueable, SerializesModels;

    private array $arrUserData;
    private UserModel $objUser;

    /**
     * Create a new message instance.
     *
     * @param array $arrUserData
     * @param UserModel $objUser
     */
    public function __construct(array $arrUserData, UserModel $objUser)
    {
        $this->arrUserData = $arrUserData;
        $this->objUser = $objUser;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->from(config("constant.email.soundblock.address"), config("constant.email.soundblock.name"));
        $this->subject("Royalties Posted");
        $this->withSwiftMessage(function ($message) {
            $message->app = App::where("app_name", "soundblock")->first();
        });
        $frontendUrl = app_url("soundblock", "http://localhost:4200") . "account/payment/balance";

        foreach ($this->arrUserData as $key => $arrPlatformData) {
            $objPlatform = PlatformModel::find($arrPlatformData["platform_id"]);
            $this->arrUserData[$key]["platform_name"] = $objPlatform->name;
        }

        return ($this->view("mail.soundblock.report_processed")->with([
            "link" => $frontendUrl,
            "arrData" => $this->arrUserData,
            "userName" => $this->objUser->name,
        ]));
    }
}
