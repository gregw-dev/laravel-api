<?php

namespace App\Services\Soundblock\Payment;

use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use App\Models\{
    Users\User as UserModel,
    Soundblock\Payments\MusicUserBalancePayment as MusicUserBalancePaymentModel
};
use App\Services\Common\App as AppService;
use App\Events\Office\Mailing\Withdrawal as WithdrawalEvent;
use App\Repositories\{
    Soundblock\Payments\MusicUserBalancePayment as MusicUserBalancePaymentRepo,
    Accounting\Banking as BankingRepo,
    Accounting\PayMethod as PayMethodRepo
};

class MusicPaymentBalance
{
    /** @var MusicUserBalancePaymentRepo */
    private MusicUserBalancePaymentRepo $musicUserBalancePaymentRepo;
    /** @var BankingRepo */
    private BankingRepo $bankingRepo;
    /** @var PayMethodRepo */
    private PayMethodRepo $payMethodRepo;
    /** @var AppService */
    private AppService $appService;

    /**
     * MusicPaymentBalance constructor.
     * @param MusicUserBalancePaymentRepo $musicUserBalancePaymentRepo
     * @param BankingRepo $bankingRepo
     * @param PayMethodRepo $payMethodRepo
     * @param AppService $appService
     */
    public function __construct(MusicUserBalancePaymentRepo $musicUserBalancePaymentRepo, BankingRepo $bankingRepo,
                                PayMethodRepo $payMethodRepo, AppService $appService){
        $this->musicUserBalancePaymentRepo = $musicUserBalancePaymentRepo;
        $this->bankingRepo = $bankingRepo;
        $this->payMethodRepo = $payMethodRepo;
        $this->appService = $appService;
    }

    public function find(string $strBalanceUuid){
        return ($this->musicUserBalancePaymentRepo->find($strBalanceUuid));
    }

    /**
     * @param UserModel $objUser
     * @return mixed
     */
    public function findUserCurrentBalance(UserModel $objUser){
        return ($this->musicUserBalancePaymentRepo->findCurrentByUser($objUser->user_uuid));
    }

    public function findAllByUser(UserModel $objUser, int $perPage = 10){
        $objRecords = $this->musicUserBalancePaymentRepo->findAllByUser($objUser->user_uuid, $perPage);

        foreach ($objRecords as $key => $objRecord) {
            $objPlatform = $objRecord->platform;
            $strMemo = $objRecord->payment_memo;

            if ($objPlatform) {
                $pos = strpos($objRecord->payment_memo, $objPlatform->name);
                $strMemo = substr_replace($objRecord->payment_memo, "", $pos, strlen($objPlatform->name));
            }

            $regex = "/\((.*?)\)/";
            preg_match($regex, $strMemo, $matches);
            if (!empty($matches)) {
                $objRecord->payment_memo = trim(str_replace("  ", " ", str_replace($matches[0], "", $objRecord->payment_memo)));
                $objRecord->payment_memo_details = $matches[1];
            }
            $objRecord->payment_amount = number_format($objRecord->payment_amount, 10);
        }

        return ($objRecords);
    }

    /**
     * @param UserModel $objUser
     * @param int $perPage
     * @return mixed
     */
    public function findUserBalanceEarnedHistory(UserModel $objUser, int $perPage = 10){
        $objRecords = $this->musicUserBalancePaymentRepo->findUserEarnedHistory($objUser->user_uuid, $perPage);

        foreach ($objRecords as $key => $objRecord) {
            if (is_null($objRecord->withdrawal_method) && !empty($objRecord->platform_uuid)) {
                $objPlatform = $objRecord->platform;
                $pos = strpos($objRecord->payment_memo, $objPlatform->name);
                $strMemo = substr_replace($objRecord->payment_memo, "", $pos, strlen($objPlatform->name));
                $regex = "/\((.*?)\)/";
                preg_match($regex, $strMemo, $matches);
                if (!empty($matches)) {
                    $objRecord->payment_memo = str_replace("  ", " ", str_replace($matches[0], "", $objRecord->payment_memo));
                    $objRecord->payment_memo_details = $matches[0];
                }
            }
            $objRecord->payment_amount = number_format($objRecord->payment_amount, 10);
        }

        return ($objRecords);
    }

    /**
     * @param UserModel $objUser
     * @param int $perPage
     * @return mixed
     */
    public function findUserBalanceWithdrawalHistory(UserModel $objUser, int $perPage = 10){
        return ($this->musicUserBalancePaymentRepo->findUserWithdrawalHistory($objUser->user_uuid, $perPage));
    }

    public function getDataForBootloader(UserModel $objUser){
        $arrUserEarnings = [];
        $objUserCurrentBalance = $this->musicUserBalancePaymentRepo->findCurrentByUser($objUser->user_uuid);

        for ($i = 1; $i <= 7; $i++) {
            $objUserEarningsHistory = $this->musicUserBalancePaymentRepo->findUserEarnedHistoryByDates(
                $objUser->user_uuid,
                Carbon::now()->subMonths($i)->startOfMonth()->format("Y"),
                Carbon::now()->subMonths($i)->endOfMonth()->format("m"),
            );
            $arrUserEarnings[Carbon::now()->subMonths($i)->format("m/y")] = $objUserEarningsHistory->sum("payment_amount");
        }

        $floatUserBalance = $objUserCurrentBalance ? $objUserCurrentBalance->user_balance : 0.0;
        ksort($arrUserEarnings);

        return ([$floatUserBalance, $arrUserEarnings]);
    }

    /**
     * @param UserModel $objUser
     * @param MusicUserBalancePaymentModel $objUserCurrentBalance
     * @param array $arrWithdrawalData
     * @return array
     * @throws \Exception
     */
    public function withdrawal(UserModel $objUser, MusicUserBalancePaymentModel $objUserCurrentBalance, array $arrWithdrawalData){
        $arrInsertData = [];
        $arrInsertData["user_id"] = $objUser->user_id;
        $arrInsertData["user_uuid"] = $objUser->user_uuid;
        $arrInsertData["withdrawal_method"] = $arrWithdrawalData["withdrawal_method"];

        if ($arrWithdrawalData["withdrawal_method"] == "banking") {
            $objWithdrawalMethod = $this->bankingRepo->find($arrWithdrawalData["withdrawal_uuid"]);
            $arrInsertData["payment_memo"] = "Withdrawal via Bank Account ***" . substr($objWithdrawalMethod->account_number, -4);
            $arrWithdrawalMethodData = [
                "bank_name"    => $objWithdrawalMethod->getOriginal("bank_name"),
                "account_type" => $objWithdrawalMethod->getOriginal("account_type"),
                "account_number" => $objWithdrawalMethod->getOriginal("account_number"),
                "routing_number" => $objWithdrawalMethod->getOriginal("routing_number")
            ];
        } elseif ($arrWithdrawalData["withdrawal_method"] == "paymethod") {
            $objWithdrawalMethod = $this->payMethodRepo->find($arrWithdrawalData["withdrawal_uuid"]);
            $arrInsertData["payment_memo"] = "Withdrawal via " . $objWithdrawalMethod->paymethod_type;
            $arrWithdrawalMethodData = [
                "paymethod_type" => $objWithdrawalMethod->paymethod_type,
                "paymethod_account" => $objWithdrawalMethod->getOriginal("paymethod_account")
            ];
        } else {
            return ([false, "Wrong withdrawal method.", $objUserCurrentBalance]);
        }

        if ($objUser->user_id != $objWithdrawalMethod->user_id) {
            return ([false, "User doesn't have this withdrawal method.", $objUserCurrentBalance]);
        }

        if ($objUserCurrentBalance->user_balance < 10 || $objUserCurrentBalance->user_balance < $arrWithdrawalData["withdrawal_amount"]) {
            return ([false, "User doesn't have enough balance for withdrawal.", $objUserCurrentBalance]);
        }

        $arrInsertData["withdrawal_method_id"] = $objWithdrawalMethod->row_id;
        $arrInsertData["withdrawal_method_uuid"] = $objWithdrawalMethod->row_uuid;
        $arrInsertData["user_balance"] = $objUserCurrentBalance->user_balance - floatval($arrWithdrawalData["withdrawal_amount"]);
        $arrInsertData["payment_amount"] = $arrWithdrawalData["withdrawal_amount"];
        $arrInsertData["withdrawal_status"] = "Pending";
        $arrInsertData["withdrawal_method_data"] = $arrWithdrawalMethodData;

        $objUserCurrentBalance = $this->musicUserBalancePaymentRepo->createWithdrawal($arrInsertData);

        /* Send notification to office */
        $strMemo = $objUser->name . "<br>" . "$" . $arrWithdrawalData["withdrawal_amount"];
        $objOfficeApp = $this->appService->findOneByName("office");
        notify_group_permission("Arena.Office", "Arena.Office.Soundblock.Payments", $objOfficeApp, "Soundblock Payment Request", $strMemo);

        event(new WithdrawalEvent(["user" => $objUser->name, "withdrawal_amount" => $arrWithdrawalData["withdrawal_amount"]]));

        return ([true, "Withdrawal successfully.", $objUserCurrentBalance]);
    }

    /**
     * @param string|null $strStatus
     * @param int $perPage
     * @return mixed
     */
    public function getWithdrawalForOffice(string $strStatus = "Pending", int $perPage = 10){
        $objWithdrawal = $this->musicUserBalancePaymentRepo->getWithdrawalForOffice($strStatus, $perPage);

        $objWithdrawal->each(function ($objWithdrawal) {
            if ($objWithdrawal->withdrawal_method == "paymethod" || $objWithdrawal->withdrawal_method == "paypal") {
                $methodAccount = $objWithdrawal->withdrawal_method_data["paymethod_account"];
                $arrWithdrawalData = [
                    "paymethod_type" => $objWithdrawal->withdrawal_method_data["paymethod_type"],
                    "paymethod_account" => empty($methodAccount) ? "" : Crypt::decrypt($methodAccount)
                ];
                $objWithdrawal->withdrawal_method_type = $objWithdrawal->withdrawal_method_data["paymethod_type"];
                $objWithdrawal->withdrawal_method_data = $arrWithdrawalData;
            } elseif ($objWithdrawal->withdrawal_method == "banking") {
                $arrWithdrawalData = [
                    "bank_name"    => Crypt::decrypt($objWithdrawal->withdrawal_method_data["bank_name"]),
                    "account_type" => Crypt::decrypt($objWithdrawal->withdrawal_method_data["account_type"]),
                    "account_number" => Crypt::decrypt($objWithdrawal->withdrawal_method_data["account_number"]),
                    "routing_number" => Crypt::decrypt($objWithdrawal->withdrawal_method_data["routing_number"])
                ];
                $objWithdrawal->withdrawal_method_data = $arrWithdrawalData;
            }
            $objWithdrawal->user_name = $objWithdrawal->user->name;
            unset($objWithdrawal->user);

        });

        return ($objWithdrawal);
    }

    /**
     * @param MusicUserBalancePaymentModel $objWithdrawal
     * @param string $strStatus
     * @return bool
     * @throws \Exception
     */
    public function updateWithdrawalStatus(MusicUserBalancePaymentModel $objWithdrawal, string $strStatus): bool{
        if ($strStatus == "Completed") {
            $this->musicUserBalancePaymentRepo->update($objWithdrawal, ["withdrawal_status" => "Completed"]);

            return (true);
        } elseif ($strStatus == "Cancelled") {
            $arrInsertData = [];
            $this->musicUserBalancePaymentRepo->update($objWithdrawal, ["withdrawal_status" => "Cancelled"]);
            $objUserCurrentBalance = $this->musicUserBalancePaymentRepo->findCurrentByUser($objWithdrawal->user_uuid);

            $arrInsertData["user_id"] = $objWithdrawal->user_id;
            $arrInsertData["user_uuid"] = $objWithdrawal->user_uuid;
            $arrInsertData["user_balance"] = $objUserCurrentBalance->user_balance + $objWithdrawal->payment_amount;
            $arrInsertData["payment_amount"] = $objWithdrawal->payment_amount;
            $arrInsertData["payment_memo"] = "Credit for Cancelled Payment";

            $objUserCurrentBalance = $this->musicUserBalancePaymentRepo->createFromCancelledWithdrawal($arrInsertData);

            return (true);
        }

        return (false);
    }
}
