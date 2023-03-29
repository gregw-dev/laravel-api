<?php

namespace App\Mail\Soundblock;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Repositories\Common\App as AppRepository;

class UserW9 extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     */
    public function __construct()
    {
        //
    }

    /**
     * Build the message.
     *
     * @param AppRepository $appRepo
     * @return $this
     */
    public function build(AppRepository $appRepo)
    {
        $this->from(config("constant.email.soundblock.address"), config("constant.email.soundblock.name"));
        $this->subject("W-9 Form Processed");
        $this->withSwiftMessage(function ($message) use ($appRepo) {
            $message->app = $appRepo->findOneByName("soundblock");
        });

        $frontendUrl = app_url("soundblock", "http://localhost:4200") . "account/payment/balance";

        return $this->view("mail.soundblock.w9")->with([
            "frontendUrl" => $frontendUrl,
        ]);
    }
}
