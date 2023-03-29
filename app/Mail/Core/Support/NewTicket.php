<?php

namespace App\Mail\Core\Support;

use App\Models\Support\Ticket\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Core\App;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewTicket extends Mailable implements ShouldQueue {
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
     */
    public function __construct(SupportTicket $objTicket,App $objApp) {
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
            $message->objMessage = $this->objTicket->messages()->first();
            $message->strSource = "support_ticket";
            $strEnv =env("APP_ENV") ==="production" ? "Web" : "Develop";
            $message->getHeaders()->addTextHeader("X-Mailgun-Variables", json_encode(["X-Arena-Env" => $strEnv]));
            $message->getHeaders()->addTextHeader("X-Mailgun-Tag", $strEnv);

        });

        return $this->view('mail.core.support.new')->from("office@support.arena.com", "Arena Office")
                    ->subject("New Ticket's Reply")->with([
                        "ticket" => $this->objTicket,
                        "app" => ucfirst($this->objApp->app_name)
                    ]);
    }
}
