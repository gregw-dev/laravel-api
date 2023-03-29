<?php

namespace App\Observers\Support\Ticket;

use App\Repositories\Office\SupportTicket as SupportTicket;
use App\Models\Support\Ticket\SupportTicketMessage as SupportTicketMessageModel;


class SupportTicketMessage
{
    /** @var SupportTicket */
    private SupportTicket $supportTicket;

    public function __construct(SupportTicket $supportTicket){
    $this->supportTicket = $supportTicket;
    }
    /**
     * Handle the SupportTicketMessage "created" event.
     *
     * @param  SupportTicketMessageModel  $supportTicketMessage
     * @return void
     */
    public function created(SupportTicketMessageModel $supportTicketMessage)
    {
        $user = optional(auth()->user())->id;
        $objTicket = $this->supportTicket->find($supportTicketMessage->ticket_uuid);
        $objTicket->stamp_updated= time();
        $objTicket->stamp_updated_at=now();
        $objTicket->stamp_updated_by = $user ? $user : 1;
        $objTicket->save();
    }

}
