<?php

namespace App\Events\Common;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CreateAccount
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $arrAuthGroup;

    /**
     * Create a new event instance.
     *
     * @param array $arrAuthGroup
     */
    public function __construct(array $arrAuthGroup)
    {
        $this->arrAuthGroup = $arrAuthGroup;
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
