<?php

namespace App\Http\Controllers\Soundblock;

use Auth;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\Soundblock\Payments\Withdrawal;
use App\Http\Requests\Soundblock\Payments\BalanceHistory;
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

    public function getUserBalance(){
        $objUser = Auth::user();

        $objUserBalance = $this->musicPaymentBalanceService->findUserCurrentBalance($objUser);

        if ($objUserBalance) {
            return ($this->apiReply($objUserBalance, "", Response::HTTP_OK, ["flag_w9" => $objUser->flag_w9]));
        }

        return ($this->apiReply([], "User don't have balance.", Response::HTTP_OK, ["flag_w9" => $objUser->flag_w9]));
    }

    public function getUserBalanceHistory(BalanceHistory $objRequest){
        $objUser = Auth::user();

        if ($objRequest->input("history_type") == "earned") {
            $objUserBalanceHistory = $this->musicPaymentBalanceService->findUserBalanceEarnedHistory($objUser, $objRequest->input("per_page", 10));
        } elseif ($objRequest->input("history_type") == "withdrawal") {
            $objUserBalanceHistory = $this->musicPaymentBalanceService->findUserBalanceWithdrawalHistory($objUser, $objRequest->input("per_page", 10));
        } else {
            $objUserBalanceHistory = $this->musicPaymentBalanceService->findAllByUser($objUser, $objRequest->input("per_page", 10));
        }

        if (!empty($objUserBalanceHistory)) {
            return ($this->apiReply($objUserBalanceHistory, "", Response::HTTP_OK));
        }

        return ($this->apiReply([], "User don't have balance history.", Response::HTTP_OK));
    }

    public function withdrawal(Withdrawal $objRequest){
        $objUser = Auth::user();

        if (!in_array($objUser->flag_w9, ["true", "notapplicable"])) {
            return ($this->apiReject(null, "W9 form is missing.", Response::HTTP_BAD_REQUEST));
        }

        $objUserCurrentBalance = $this->musicPaymentBalanceService->findUserCurrentBalance($objUser);

        if (empty($objUserCurrentBalance) || $objUserCurrentBalance->user_balance < 10) {
            return ($this->apiReject(null, "User don't have enough balance for withdrawal.", Response::HTTP_BAD_REQUEST));
        }

        [$boolResult, $strMessage, $objUserCurrentBalance] = $this->musicPaymentBalanceService->withdrawal($objUser, $objUserCurrentBalance, $objRequest->all());

        if ($boolResult) {
            return ($this->apiReply($objUserCurrentBalance, $strMessage, Response::HTTP_OK));
        }

        return ($this->apiReject($objUserCurrentBalance, $strMessage, Response::HTTP_BAD_REQUEST));
    }
}
