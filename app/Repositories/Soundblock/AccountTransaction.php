<?php

namespace App\Repositories\Soundblock;

use Util;
use App\Repositories\BaseRepository;
use App\Models\{
    Soundblock\Accounts\AccountPlan as AccountPlanModel,
    Soundblock\Accounts\Account as AccountModel,
    Soundblock\Accounts\AccountTransaction as AccountTransactionModel};

class AccountTransaction extends BaseRepository {
    /**
     * AccountTransactionRepository constructor.
     * @param AccountTransactionModel $accountTransaction
     */
    public function __construct(AccountTransactionModel $accountTransaction) {
        $this->model = $accountTransaction;
    }

    public function findPastDueTransactionDate(AccountModel $objAccount){
        return ($this->model->where("account_id", $objAccount->account_id)->where("transaction_status", "past due accounts")->latest()->first());
    }

    public function getPastDueAccountsTransactions(AccountModel $objAccount, string $strType){
        $query = $this->model->where("account_id", $objAccount->account_id)->where("transaction_status", "past due accounts");

        if ($strType == "annual") {
            $query = $query->whereNotIn("transaction_type", ["user", "bandwidth", "diskspace"]);
        } elseif ($strType == "transactions") {
            $query = $query->whereIn("transaction_type", ["user", "bandwidth", "diskspace"]);
        }

        return ($query->get());
    }

    public function storeTransaction(AccountPlanModel $objAccountPlan, string $type, float $amount, string $status): AccountTransactionModel{
        $objAccountTransaction = $objAccountPlan->account->transactions()->create([
            "transaction_uuid" => Util::uuid(),
            "account_uuid" => $objAccountPlan->account_uuid,
            "plan_type_id" => $objAccountPlan->plan_type_id,
            "plan_type_uuid" => $objAccountPlan->plan_type_uuid,
            "transaction_amount" => $amount,
            "transaction_type" => $type,
            "transaction_status" => $status,
        ]);

        return ($objAccountTransaction);
    }
}
