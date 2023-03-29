<?php

namespace App\Events\User;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    /**
     * @var string  $strUserUuid
     * user_uuid of the user to whom broadcast message need to be sent
     */
    public string $strUserUuid;
    /**
     * @var string  $strAppName
     * example "Arena", "Soundblock"
     */
    public string $strAppName;
    /**
     * @var string  $strRouteName
     * action name just a string which is used to identify which action, because multiple action are going to be passed
     * and according to each action we could manipulate the data both on backend as well as on front as well
     */
    public string $strRouteName;
    /**
     * @param array $arrPayload
     * payload is an array of all additional data that might be required,
     * and according to $strAppName and $strRouteName, you might need to manipulate it before broadcasting
     */
    public array $arrPayload = array();

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $strUserUuid, string $strAppName, string $strRouteName, array $arrPayload)
    {
        $this->strUserUuid  = $strUserUuid;
        $this->strAppName   = $strAppName;
        $this->strRouteName = $strRouteName;
        $this->arrPayload   = $arrPayload;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel(sprintf("Arena.User.%s", $this->strUserUuid));
    }

    public function broadcastAs()
    {
        return "Arena.User.".$this->strUserUuid;
    }

    public function broadcastWith()
    {
        /**
         * we are just using these single letter so that we could not hit pusher limit in case of huge data
         * a => appName
         * r => actionName
         * p => payload
         */
        return [
            'a'   => $this->strAppName,
            'r'   => $this->strRouteName,
            'p'   => $this->arrPayload
        ];
    }

}
