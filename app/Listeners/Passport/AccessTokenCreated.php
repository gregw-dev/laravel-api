<?php

namespace App\Listeners\Passport;

use DB;
use Util;

class AccessTokenCreated
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $intIpFraudScore = Util::getIpFraudScore(request()->getClientIp());
        DB::table("oauth_access_tokens")
            ->where("id", $event->tokenId)
            ->update([
                "remote_addr" => request()->getClientIp(),
                "remote_host" => gethostbyaddr(request()->getClientIp()),
                "remote_agent" => request()->server("HTTP_USER_AGENT"),
                "fraud_score" => $intIpFraudScore
            ]);
    }
}
