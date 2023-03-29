<?php

namespace App\Mail\Core;

use App\Models\Core\App;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Models\Core\Correspondence as CorrespondenceModel;
use App\Models\Core\CorrespondenceMessage as CorrespondenceMessageModel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Correspondence extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    /**
     * @var Correspondence
     */
    private $correspondence;
    /**
     * @var App
     */
    private App $app;
    /**
     * @var CorrespondenceMessageModel
     */
    private CorrespondenceMessageModel $correspondenceMessage;

    /**
     * CorrespondenceMail constructor.
     * @param CorrespondenceModel $correspondence
     * @param CorrespondenceMessageModel $correspondenceMessage
     */
    public function __construct(CorrespondenceModel $correspondence, CorrespondenceMessageModel $correspondenceMessage){
        $this->correspondence = $correspondence;
        $this->app = $correspondence->app;
        $this->correspondenceMessage = $correspondenceMessage;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $strEnv = env("APP_ENV") === "prod" ? "Web" : "Develop";

        /* Email setup */
        $this->withSwiftMessage(function ($message) use ($strEnv) {
            $message->app = $this->app;
            $message->objMessage = $this->correspondence->messages()->first();
            $message->strSource = "confirmation";
            $message->getHeaders()->addTextHeader("X-Mailgun-Variables", json_encode(["X-Arena-Env" => $strEnv]));
            $message->getHeaders()->addTextHeader("X-Mailgun-Tag", $strEnv);
        });
        $appName = config("constant.email.". $this->app->app_name .".name");
        $emailFrom = config("constant.email.". $this->app->app_name .".address");

        $this->from(config("constant.email.office.address"), config("constant.email.office.name"));
        $this->subject("Arena " . $appName . ": Correspondence");

        /* Get data for template */
        if(!empty($this->correspondenceMessage->email_json)) {
            $arrJsonData = json_decode($this->correspondenceMessage->email_json, true);
            $arrJsonData = array_filter((array) $arrJsonData, function ($val) {
                return !empty(str_replace(" ", "", $val));
            });
            $arrJsonData["Location"] = implode(",", $arrJsonData["Location"]);
            $arrJsonData["message_type"] = "json";
        } else {
            $arrJsonData = [
                "message_type" => "text",
                "message_body" => $this->correspondenceMessage->email_text
            ];
        }

        $attachments = [];
        $objAttachments = $this->correspondenceMessage->files;

        if (!empty($objAttachments) && count($objAttachments) > 0) {
            foreach ($objAttachments as $key => $objAttachment) {
                $attachments[$key]["name"] = $objAttachment->attachment_name;
                $attachments[$key]["url"] = $objAttachment->attachment_url;
            }
        }

        /* Send email */
        return ($this->view("mail.core.correspondence")->with([
            "arrJsonData" => $arrJsonData,
            "app" => $appName,
            "attachments" => $attachments,
            "correspondence" => $this->correspondence,
            "strConfirmationPage" => null,
            "contactEmail" => $emailFrom
        ]));
    }
}
