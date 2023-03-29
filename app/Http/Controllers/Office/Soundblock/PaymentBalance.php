<?php

namespace App\Http\Controllers\Office\Soundblock;

use Auth;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\Office\Soundblock\Payment\GetWithdrawal;
use App\Http\Requests\Office\Soundblock\Payment\UpdateWithdrawal;
use App\Services\Soundblock\Payment\MusicPaymentBalance as MusicPaymentBalanceService;

class PaymentBalance extends Controller
{
    /** @var MusicPaymentBalanceService */
    private MusicPaymentBalanceService $musicPaymentBalanceService;

    /**
     * PaymentBalance constructor.
     * @param MusicPaymentBalanceService $musicPaymentBalanceService
     */
    public function __construct(MusicPaymentBalanceService $musicPaymentBalanceService){
        $this->musicPaymentBalanceService = $musicPaymentBalanceService;
    }

    /**
     * @param GetWithdrawal $objRequest
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Resources\Json\ResourceCollection|Response|object
     */
    public function getWithdrawal(GetWithdrawal $objRequest){
        if (!is_authorized(Auth::user(), "Arena.Office", "Arena.Office.Soundblock.Payments", "office")) {
            return $this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN);
        }

        $objWithdrawal = $this->musicPaymentBalanceService->getWithdrawalForOffice($objRequest->input("withdrawal_status", "Pending"), $objRequest->input("per_page", 10));

        return ($this->apiReply($objWithdrawal, "", Response::HTTP_OK));
    }

    /**
     * @param UpdateWithdrawal $objRequest
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Resources\Json\ResourceCollection|Response|object
     * @throws \Exception
     */
    public function updateWithdrawalStatus(UpdateWithdrawal $objRequest){
        if (!is_authorized(Auth::user(), "Arena.Office", "Arena.Office.Soundblock.Payments", "office")) {
            return $this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN);
        }

        $objWithdrawal = $this->musicPaymentBalanceService->find($objRequest->input("withdrawal_uuid"));

        if ($objWithdrawal->withdrawal_status != "Pending") {
            return ($this->apiReject(null, "Withdrawal already processed.", Response::HTTP_BAD_REQUEST));
        }

        $boolResult = $this->musicPaymentBalanceService->updateWithdrawalStatus($objWithdrawal, $objRequest->input("withdrawal_status"));

        if ($boolResult) {
            return ($this->apiReply(null, "Status was changed successfully.", Response::HTTP_OK));
        }

        return ($this->apiReject(null, "Invalid status.", Response::HTTP_BAD_REQUEST));
    }
}
