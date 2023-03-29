<?php

namespace App\Mail\Core\Support;

use App\Models\Support\Ticket\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Core\App;
use Illuminate\Contracts\Queue\ShouldQueue;

class TicketOpened extends Mailable implements ShouldQueue {
    use Queueable, SerializesModels;

    /**
     * @var SupportTicket
     */
    private SupportTicket $objTicket;

    private App $objApp;

    /**
     * Create a new message instance.
     *
     * @param SupportTicket $objTicket
     * @param App $objApp
     */
    public function __construct(SupportTicket $objTicket, App $objApp) {
        $this->objTicket = $objTicket;
        $this->objApp = $objApp;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {
        $this->withSwiftMessage(function ($message) {
            $message->app = $this->objApp;
        });
        $appName = config("constant.email.". $this->objApp->app_name .".name");
        $this->from(config("constant.email.". $this->objApp->app_name .".address"), $appName);
        $this->subject("Support Ticket: " . $this->objTicket->user->name . ": " . $this->objTicket->ticket_title);
        $soundTicketUrl = app_url("soundblock", "http://localhost:4200") . "support?ticket_id=" . $this->objTicket->ticket_uuid;

        return $this->view("mail.core.support.opened")->with([
            "link" => $soundTicketUrl
        ]);
    }
}
