<?php

namespace App\Mail\Core;

use App\Models\Core\App;
use App\Models\Core\Correspondence as CorrespondenceModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CorrespondenceConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @var Correspondence
     */
    public $correspondence;
    /**
     * @var App
     */
    private App $app;
    /**
     * @var String
     */
    protected $strConfirmationPage;

    /**
     * CorrespondenceMail constructor.
     * @param App $app
     * @param CorrespondenceModel $correspondence
     * @param string $strConfirmationPage
     */
    public function __construct(App $app, CorrespondenceModel $correspondence, string $strConfirmationPage){
        $this->app = $app;
        $this->correspondence = $correspondence;
        $this->strConfirmationPage = $strConfirmationPage;
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
        $this->from($emailFrom, "Arena " . $appName);
        $this->subject($this->correspondence->email_subject);

        /* Get data for template */
        $strEmailJsonString = $this->correspondence->messages[0]->email_json;

        if(!empty($strEmailJsonString)) {
            $arrJsonData = json_decode($this->correspondence->messages[0]->email_json, true);
            $arrJsonData = array_filter((array) $arrJsonData, function ($val) {
                return !empty(str_replace(" ", "", $val));
            });
            $arrJsonData["Location"] = implode(",", $arrJsonData["Location"]);
            $arrJsonData["message_type"] = "json";
        } else {
            $arrJsonData = [
                "message_type" => "text",
                "message_body" => $this->correspondence->messages[0]->email_text
            ];
        }

        $attachments = [];
        $objAttachments = $this->correspondence->messages[0]->files;

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
            "strConfirmationPage" => $this->strConfirmationPage,
            "contactEmail" => $emailFrom
        ]));
    }
}
