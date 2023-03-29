<?php

namespace App\Listeners\Common;

use Illuminate\Mail\Events\MessageSent as MessageSentEvent;
use App\Services\Core\Correspondence;

class MessageSent {

    /**
     * @var Correspondence
     */
    private Correspondence $correspondenceService;


    /**
     * Create the event listener.
     * @param Correspondence $correspondenceService
     *
     */
    public function __construct(Correspondence $correspondenceService) {
        $this->correspondenceService = $correspondenceService;

    }

    /**
     * Handle the event.
     *
     * @param MessageSentEvent $event
     * @return void
     * @throws \Exception
     */
    public function handle(MessageSentEvent $event) {
        try {
            $arrAllowed = ["confirmation","response"];
            if(property_exists($event->message, 'strSource') && in_array($event->message->strSource,$arrAllowed)){
                $messageId= (string) $event->message->getHeaders()->get("message-id");
                $arrMessageId = explode(": ",$messageId);
                $strMessageId = trim($arrMessageId[1]);
                $objMessage = $event->message->objMessage;
                $this->correspondenceService->storeCorrespondenceLookup($objMessage->correspondence,$strMessageId);
            }
        } catch (\Exception $exception) {
            return;
        }
    }
}
