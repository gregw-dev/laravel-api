<?php

namespace App\Jobs\Office;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Pagination\LengthAwarePaginator as SupportTicketMessageModel;

class UpdateSupportTicketMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    /**
     * @var SupportTicketMessageModel
     */
    protected $objMessages;
    /**
     * @var Bool
     */
    protected $blnFlagOffice;
    /**
     * @param SupportTicketMessageModel $objMessages
     * @param Bool $blnFlagOffice
     */
    public function __construct(SupportTicketMessageModel $objMessages, Bool $blnFlagOffice )
    {
        $this->objMessages = $objMessages;
        $this->blnFlagOffice = $blnFlagOffice;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->objMessages as $objMessage) {
            $objMessage["user"]["avatar"] = $objMessage->user->avatar;
            if (!$this->blnFlagOffice) {
                $objMessage->update(["flag_status" => "Read"]);
            }
        }
    }
}
