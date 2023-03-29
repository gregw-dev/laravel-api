<?php

namespace App\Events\Office\Mailing;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Withdrawal
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $arrWithdrawalData;

    /**
     * Create a new event instance.
     *
     * @param array $arrWithdrawalData
     */
    public function __construct(array $arrWithdrawalData)
    {
        $this->arrWithdrawalData = $arrWithdrawalData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
