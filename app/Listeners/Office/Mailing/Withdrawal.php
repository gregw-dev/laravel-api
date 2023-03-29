<?php

namespace App\Listeners\Office\Mailing;

use App\Services\Core\Auth\AuthGroup as AuthGroupService;
use Mail;
use App\Mail\Office\Withdrawal as WithdrawalMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class Withdrawal implements ShouldQueue
{
    private array $arrWithdrawalData;
    /**
     * @var AuthGroupService
     */
    private AuthGroupService $authGroupService;

    /**
     * Create the event listener.
     * @param AuthGroupService $authGroupService
     */
    public function __construct(AuthGroupService $authGroupService)
    {
        $this->authGroupService = $authGroupService;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $arrWithdrawalData = $event->arrWithdrawalData;
        $objOfficeUsers = $this->authGroupService->findAllUsersByGroupAndPermission("Arena.Office", "Arena.Office.Soundblock.Payments");
        $arrMailData = [
            "user" => $arrWithdrawalData["user"],
            "withdrawal_amount" => $arrWithdrawalData["withdrawal_amount"]
        ];
        foreach ($objOfficeUsers as $objOfficeUser) {
            Mail::to($objOfficeUser->primary_email->user_auth_email)->send(new WithdrawalMail($arrMailData, $objOfficeUser));
        }
    }
}
