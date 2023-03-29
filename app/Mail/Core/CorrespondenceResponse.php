<?php

namespace App\Mail\Core;

use App\Models\Core\App;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Core\CorrespondenceMessage as CorrespondenceResponseModel;

class CorrespondenceResponse extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @var App
     */
    private App $app;
    /**
     * @var CorrespondenceResponseModel
     */
    private CorrespondenceResponseModel $correspondenceResponse;

    /**
     * Create a new message instance.
     *
     * @param App $app
     * @param CorrespondenceResponseModel $correspondence
     */
    public function __construct(App $app, $correspondenceResponse)
    {
        $this->app = $app;
        $this->correspondenceResponse = $correspondenceResponse;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $attachments = [];
        $objAttachments = $this->correspondenceResponse->files;
        if (!empty($objAttachments) && count($objAttachments)>0 ) {
            foreach ($objAttachments as $key => $objAttachment) {
                $attachments[$key]["name"] = $objAttachment->attachment_name;
                $attachments[$key]["url"] = $objAttachment->attachment_url;
            }
        }

        $this->withSwiftMessage(function ($message) {
            $message->app = $this->app;
            $message->objMessage = $this->correspondenceResponse;
            $message->strSource = "response";
        });

        $appName = config("constant.email.". $this->app->app_name .".name");
        $this->from(config("constant.email.". $this->app->app_name .".address"), "Arena " . $appName);
        $this->subject("Arena Correspondence");

                /* Get data for template */
                $strEmailJsonString = $this->correspondenceResponse->email_json;
                if(!empty($strEmailJsonString)){
                         $arrJsonData = json_decode($this->correspondenceResponse->email_json);
                $arrJsonData = array_filter((array) $arrJsonData, function ($val) {
                    return !empty(str_replace(" ", "", $val));
                });
                $arrJsonData["message_type"] = "json";
                }else{
                $arrJsonData = [
                    "message_type" => "text",
                    "message_body" => $this->correspondenceResponse->email_text
                ];
                }

        return ($this->view("mail.core.correspondenceResponse")
            ->with([
                "arrJsonData" => $arrJsonData,
                "app" => $appName,
                "attachments" => $attachments,
                "correspondence" => $this->correspondenceResponse->correspondence
                ])
        );
    }
}
