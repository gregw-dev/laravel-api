<?php

namespace App\Services\Common;

use App\Jobs\Soundblock\Ledger\ServiceLedger;
use App\Services\Soundblock\Ledger\ServiceLedger as ServiceLedgerService;
use Carbon\Carbon;
use Laravel\Cashier\PaymentMethod;
use Util;
use App\Contracts\Core\Slack as SlackService;
use App\Services\Soundblock\Accounting\Charge;
use App\Contracts\Soundblock\Accounting\Accounting;
use App\Contracts\Soundblock\Account\AccountPlan as ServicePlanContract;
use App\Repositories\{
    Accounting\AccountingType as AccountingTypeRepository,
    Accounting\AccountingTypeRate as AccountingTypeRateRepository,
    Common\AccountPlan as AccountPlanRepository,
    Common\Account as AccountRepository,
    Soundblock\Data\PlansTypes as PlansTypesRepository,
    Soundblock\AccountTransaction as AccountTransactionRepository,
    Soundblock\AccountInvoice as AccountInvoiceRepository
};
use App\Models\{
    Soundblock\Accounts\Account,
    Soundblock\Accounts\AccountPlan as AccountPlanModel,
    Users\User,
    Soundblock\Data\PlansType as PlansTypeModel
};

class AccountPlan implements ServicePlanContract {
    /** @var AccountRepository */
    protected AccountRepository $accountRepo;
    /** @var AccountPlanRepository */
    protected AccountPlanRepository $planRepo;
    /** @var AccountingTypeRepository */
    protected AccountingTypeRepository $accountingTypeRepo;
    /** @var AccountingTypeRateRepository */
    protected AccountingTypeRateRepository $accountingTypeRateRepo;
    /** @var Accounting */
    private Accounting $accounting;
    /** @var Charge */
    private Charge $chargeService;
    /** @var PlansTypesRepository */
    private PlansTypesRepository $plansTypesRepo;
    /** @var AccountTransactionRepository */
    private AccountTransactionRepository $accountTransactionRepo;
    /** @var AccountInvoiceRepository */
    private AccountInvoiceRepository $accountInvoiceRepo;
    /** @var SlackService */
    private SlackService $slackService;

    /**
     * @param AccountRepository $accountRepo
     * @param AccountPlanRepository $planRepo
     * @param AccountingTypeRepository $accountingTypeRepo
     * @param Charge $chargeService
     * @param AccountingTypeRateRepository $accountingTypeRateRepo
     *
     * @param Accounting $accounting
     * @param PlansTypesRepository $plansTypesRepo
     * @param AccountTransactionRepository $accountTransactionRepo
     * @param AccountInvoiceRepository $accountInvoiceRepo
     * @param SlackService $slackService
     */
    public function __construct(AccountRepository $accountRepo, AccountPlanRepository $planRepo,
                                AccountingTypeRepository $accountingTypeRepo, Charge $chargeService,
                                AccountingTypeRateRepository $accountingTypeRateRepo, Accounting $accounting,
                                PlansTypesRepository $plansTypesRepo, AccountTransactionRepository $accountTransactionRepo,
                                AccountInvoiceRepository $accountInvoiceRepo, SlackService $slackService) {
        $this->accountRepo = $accountRepo;
        $this->planRepo = $planRepo;
        $this->accountingTypeRepo = $accountingTypeRepo;
        $this->accountingTypeRateRepo = $accountingTypeRateRepo;
        $this->accounting = $accounting;
        $this->chargeService = $chargeService;
        $this->plansTypesRepo = $plansTypesRepo;
        $this->accountTransactionRepo = $accountTransactionRepo;
        $this->accountInvoiceRepo = $accountInvoiceRepo;
        $this->slackService = $slackService;
    }

    public function find($id, bool $bnFailure = true): ?AccountPlanModel {
        return ($this->planRepo->find($id, $bnFailure));
    }

    public function getActivePlan(Account $objAccount) {
        return $objAccount->plans()->where("flag_active", true)->orderBy("version", "desc")->first();
    }

    public function getActualPlansTypes(){
        $objPlanTypes = $this->plansTypesRepo->all();
        $objPlanTypes = $objPlanTypes->sortByDesc("plan_version")->groupBy("plan_level");
        $objPlanTypes = $objPlanTypes->map(function ($item) {
            return ($item->first());
        });

        return ($objPlanTypes->sortBy("plan_level")->values());
    }

    public function getAccountsPastDue(Account $objAccount){
        $arrData = [];
        $annualAmount = 0;
        $transactionsAmount = 0;
        $objAnnualTransaction = $this->accountTransactionRepo->getPastDueAccountsTransactions($objAccount, "annual");
        $objAdditionalTransactions = $this->accountTransactionRepo->getPastDueAccountsTransactions($objAccount, "transactions");

        if ($objAnnualTransaction->count() > 0) {
            $annualAmount = $objAnnualTransaction->last()->transaction_amount;
        }

        if ($objAdditionalTransactions->count() > 0) {
            $transactionsAmount = $objAdditionalTransactions->sum("transaction_amount");
        }

        $transactionsAmount = 0;

        $arrData["annual"] = $annualAmount;
        $arrData["transactions"] = $transactionsAmount;
        $arrData["total"] = $annualAmount + $transactionsAmount;

        return ($arrData);
    }

    public function getAccountPastDueDate(Account $objAccount){
        $objTransaction = $this->accountTransactionRepo->findPastDueTransactionDate($objAccount);

        return (Carbon::parse($objTransaction->stamp_created_at)->format("m/d/Y"));
    }

    public function chargePastDue(Account $objAccount, PaymentMethod $paymentMethod){
        $arrData = $this->getAccountsPastDue($objAccount);
        $totalAmount = $arrData["total"];

        if ($totalAmount > 0) {
            $boolResult = $this->accounting->chargePastDueAccount($objAccount, $paymentMethod);

            if ($boolResult) {
                $objAccount->update(["flag_status" => "active"]);

                return ([true, $objAccount]);
            }

            return ([false, $objAccount]);
        }

        $objAccount->update(["flag_status" => "active"]);

        return ([true, $objAccount]);
    }

    /**
     * @param Account $objAccount
     * @param PlansTypeModel $objPlanType
     * @return AccountPlanModel
     * @throws \Exception
     */
    public function create(Account $objAccount, PlansTypeModel $objPlanType): AccountPlanModel {
        // Create a Account Plan
        $objAccountPlan = $this->planRepo->create([
            "account_id"     => $objAccount->account_id,
            "account_uuid"   => $objAccount->account_uuid,
            "plan_type_id"   => $objPlanType->data_id,
            "plan_type_uuid" => $objPlanType->data_uuid,
            "service_date"   => now(),
            "flag_active"    => false,
        ]);

        $status = "not paid";
        $active = false;

        $objAccount->refresh();

        $objAccountTransaction = $this->accountTransactionRepo->storeTransaction($objAccountPlan, $objPlanType->plan_name, $objPlanType->plan_rate, $status);
        $boolResult = $this->accounting->accountPlanCharge($objAccount, $objAccountTransaction, floatval($objPlanType->plan_rate));

        if ($boolResult) {
            $status = "paid";
            $active = true;
            $objAccountPlan->update(["flag_active" => $active]);
        } else {
            $this->slackService->chargeFailedReport("create account plan", $objAccount->account_name, $objAccount->account_uuid, $objPlanType->plan_name);
        }

        return ($objAccountPlan);
    }

//    /**
//     * @param User $user
//     * @param PlansTypeModel $objPlanType
//     * @return array
//     * @throws \Exception
//     */
//    public function update(User $user, PlansTypeModel $objPlanType): array {
//        $objAccount = $user->account;
//
//        $objAccount->plans()->active()->update([
//            "flag_active" => false,
//        ]);
//
//        $objAccount->plans()->create([
//            "plan_uuid"      => Util::uuid(),
//            "account_uuid"   => $objAccount->account_uuid,
//            "plan_type_id"   => $objPlanType->data_id,
//            "plan_type_uuid" => $objPlanType->data_uuid,
//            "service_date"   => now()->toDate(),
//            "flag_active"    => true,
//        ]);
//
//        $objAccount->update([
//            "flag_status" => "active",
//        ]);
//
//        return $objAccount;
//    }

    public function update(Account $objAccount, PlansTypeModel $objPlanType, AccountPlanModel $objOldPlan): Account {
        $objAccount->plans()->active()->update([
            "flag_active" => false,
        ]);

        $objAccountPlan = $objAccount->plans()->create([
            "plan_uuid"      => Util::uuid(),
            "account_uuid"   => $objAccount->account_uuid,
            "plan_type_id"   => $objPlanType->data_id,
            "plan_type_uuid" => $objPlanType->data_uuid,
            "service_date"   => now()->toDate(),
            "flag_active"    => false,
        ]);

        $objAccount->update([
            "flag_status" => "active",
        ]);

        if ($objOldPlan->planType->plan_rate < $objPlanType->plan_rate) {
            $amount = $objPlanType->plan_rate - $objOldPlan->planType->plan_rate;
            $objAccountTransaction = $this->accountTransactionRepo->storeTransaction($objAccountPlan, $objPlanType->plan_name, $amount, "not paid");
            $boolResult = $this->accounting->accountPlanCharge($objAccount, $objAccountTransaction, floatval($amount));

            if ($boolResult) {
                $objAccountPlan->update(["flag_active" => true]);
                dispatch(new ServiceLedger(
                    $objAccount,
                    ServiceLedgerService::UPDATE_EVENT,
                    [
                        "remote_addr" => request()->getClientIp(),
                        "remote_host" => gethostbyaddr(request()->getClientIp()),
                        "remote_agent" => request()->server("HTTP_USER_AGENT")
                    ]
                ))->onQueue("ledger");
            } else {
                $objAccountTransaction->update(["transaction_status" => "past due accounts"]);
                $objAccount->update(["flag_status" => "past due accounts"]);
                $this->slackService->chargeFailedReport("upgrade plan", $objAccount->account_name, $objAccount->account_uuid, $objPlanType->plan_name);
            }
        }

        return $objAccount;
    }

    /**
     * @param $objAccountPlan
     * @param string $planDay
     * @return mixed
     */
    public function updatePlanChargeDay($objAccountPlan, string $planDay) {
        return ($this->planRepo->update($objAccountPlan, ["service_date" => $planDay]));
    }

    public function cancel(User $user): Account {
        $objAccount = $user->account;
        $objPlan = $objAccount->plans()->active()->first();

        if (is_null($objPlan)) {
            throw new \Exception("User's account plan not found.");
        }

        $objPlan->flag_active = false;

        $objAccount->save();
        $objPlan->save();

        $this->create($objAccount, $this->plansTypesRepo->getSimpleType());

        return $objAccount;
    }

    public function cancelNew(Account $objAccount): Account {
        $objPlan = $objAccount->plans()->active()->first();

        if (is_null($objPlan)) {
            throw new \Exception("User's account plan not found.");
        }

        $objPlan->flag_active = false;

        $objAccount->save();
        $objPlan->save();

        $this->create($objAccount, $this->plansTypesRepo->getSimpleType());

        return $objAccount;
    }
}
