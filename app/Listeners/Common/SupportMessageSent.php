<?php

namespace App\Listeners\Common;

use Illuminate\Mail\Events\MessageSent as MessageSentEvent;
use App\Services\Core\Correspondence;
use App\Services\Office\SupportTicket;

class SupportMessageSent {

    /**
     * @var SupportTicket
     */
    private SupportTicket $supportTickService;


    /**
     * Create the event listener.
     * @param SupportTicket $supportTickService
     *
     */
    public function __construct(SupportTicket $supportTickService) {
        $this->supportTickService = $supportTickService;
    }

    /**
     * Handle the event.
     *
     * @param MessageSentEvent $event
     * @return void
     * @throws \Exception
     */
    public function handle(MessageSentEvent $event) {
        $arrAllowed = ["support_ticket"];

        if(property_exists($event->message, 'strSource') && in_array($event->message->strSource, $arrAllowed)){
            $messageId = (string) $event->message->getHeaders()->get("message-id");
            $arrMessageId = explode(": ", $messageId);
            $strMessageId = trim($arrMessageId[1]);
            $this->supportTickService->storeSupportTicketLookup($event->message->objTicket, $strMessageId);
        }
    }
}
