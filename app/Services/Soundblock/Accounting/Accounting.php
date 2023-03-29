<?php

namespace App\Services\Soundblock\Accounting;

use Carbon\Carbon;
use App\Facades\Soundblock\Accounting\Charge;
use App\Jobs\Soundblock\Ledger\ServiceLedger;
use App\Helpers\Soundblock as SoundblockHelper;
use App\Exceptions\Core\Disaster\PaymentTaskException;
use App\Services\Soundblock\Ledger\ServiceLedger as ServiceLedgerService;
use Laravel\Cashier\{Exceptions\IncompletePayment, PaymentMethod};
use App\Contracts\Soundblock\Accounting\Accounting as AccountingContract;
use App\Models\{
    Soundblock\Accounts\Account,
    Soundblock\Accounts\AccountTransaction,
    Users\User};
use App\Repositories\{
    Accounting\AccountingInvoice as AccountingInvoiceRepository,
    Accounting\AccountingFailedPayments,
    Common\Account as AccountRepository,
    Soundblock\ProjectsBandwidth,
    Soundblock\Reports\DiskSpace as DiskSpaceRepository,
    Soundblock\AccountInvoice as AccountInvoiceRepository,
    Soundblock\AccountTransaction as AccountTransactionRepository,
};

class Accounting implements AccountingContract {
    /** @var AccountingInvoiceRepository */
    private AccountingInvoiceRepository $accountingInvoiceRepository;
    /** @var AccountingFailedPayments */
    private AccountingFailedPayments $accountingFailedPaymentsRepository;
    /** @var ProjectsBandwidth */
    private ProjectsBandwidth $projectsBandwidthRepo;
    /** @var AccountRepository */
    private AccountRepository $accountRepository;
    /** @var AccountInvoiceRepository */
    private AccountInvoiceRepository $accountInvoiceRepo;
    /** @var DiskSpaceRepository */
    private DiskSpaceRepository $diskSpaceRepo;
    /** @var AccountTransactionRepository */
    private AccountTransactionRepository $accountTransactionRepo;

    /**
     * AccountingService constructor.
     * @param AccountingInvoiceRepository $accountingInvoiceRepository
     * @param AccountingFailedPayments $accountingFailedPaymentsRepository
     * @param ProjectsBandwidth $projectsBandwidthRepo
     * @param AccountRepository $accountRepository
     * @param AccountInvoiceRepository $accountInvoiceRepo
     * @param DiskSpaceRepository $diskSpaceRepo
     * @param AccountTransactionRepository $accountTransactionRepo
     */
    public function __construct(AccountingInvoiceRepository $accountingInvoiceRepository,
                                AccountingFailedPayments $accountingFailedPaymentsRepository, ProjectsBandwidth $projectsBandwidthRepo,
                                AccountRepository $accountRepository, AccountInvoiceRepository $accountInvoiceRepo,
                                DiskSpaceRepository $diskSpaceRepo, AccountTransactionRepository $accountTransactionRepo) {
        $this->accountingInvoiceRepository = $accountingInvoiceRepository;
        $this->accountingFailedPaymentsRepository = $accountingFailedPaymentsRepository;
        $this->projectsBandwidthRepo = $projectsBandwidthRepo;
        $this->accountRepository = $accountRepository;
        $this->accountInvoiceRepo = $accountInvoiceRepo;
        $this->diskSpaceRepo = $diskSpaceRepo;
        $this->accountTransactionRepo = $accountTransactionRepo;
    }

    public function chargeUserImmediately(User $user, PaymentMethod $paymentMethod): bool {
        $objAccounts = $user->userAccounts()->where("flag_status", "past due accounts")->get();

        foreach ($objAccounts as $objAccount) {
            $objTransactions = $objAccount->transactions()->where("transaction_status", "past due accounts")->get();
            $amount = $objTransactions->sum("transaction_amount");

            if ($amount == 0.0) {
                return true;
            }

            $payment = $user->charge($amount * 100, $paymentMethod->id);
            $boolResult = $this->accountInvoiceRepo->storeInvoice($objTransactions, $amount, $payment);

            if ($boolResult) {
                foreach ($objTransactions as $objTransaction) {
                    $objTransaction->update(["transaction_status" => "paid"]);
                }

                return true;
            } else {
                if (!$boolResult) {
                    throw new \Exception("Something Went Wrong.", 400);
                }
            }
        }

        return (true);
    }

    public function chargePastDueAccount(Account $objAccount, PaymentMethod $paymentMethod): bool{
        $objUser = $objAccount->user;
        $objTransactions = $this->accountTransactionRepo->getPastDueAccountsTransactions($objAccount, "annual");
//        $objAnnualTransaction = $this->accountTransactionRepo->getPastDueAccountsTransactions($objAccount, "annual");
//        $objAdditionalTransactions = $this->accountTransactionRepo->getPastDueAccountsTransactions($objAccount, "transactions");
//        $objTransactions = $objAdditionalTransactions->push($objAnnualTransaction->last());
//        $objTransactions->filter();
        $amount = $objTransactions->sum("transaction_amount");

        if ($amount == 0.0) {
            return true;
        }

        try {
            $payment = $objUser->charge($amount * 100, $paymentMethod->id);
            $this->accountInvoiceRepo->storeInvoice($objTransactions, $amount, $payment);

            foreach ($objTransactions as $objTransaction) {
                $objTransaction->update(["transaction_status" => "paid"]);
            }

            return (true);
        } catch (IncompletePayment $exception) {
            return (false);
        }
    }

    public function makeCharge(Account $account, ?PaymentMethod $paymentMethod = null): array {
        try {
            [$arrTransactions, $arrTransactionsMeta] = $this->prepareCharges($account);

            if (empty($arrTransactions)) {
                return [[], $arrTransactionsMeta, true];
            }

            $objTransactions = collect($arrTransactions);
            $amount = $objTransactions->sum("transaction_amount");

            if ($amount == 0.0) {
                foreach ($objTransactions as $objTransaction) {
                    $objTransaction->update(["transaction_status" => "paid"]);
                }

                return [[], $arrTransactionsMeta, true];
            }

            [$allMethods, $user] = $this->preparePaymentMethods($account, $paymentMethod);

            if (!empty($allMethods)) {
                foreach ($allMethods as $objMethod) {
                    try {
                        $payment = $user->charge($amount * 100, $objMethod->id);
                        $this->accountInvoiceRepo->storeInvoice($objTransactions, $amount, $payment);

                        foreach ($objTransactions as $objTransaction) {
                            $objTransaction->update(["transaction_status" => "paid"]);
                        }

                        return ([$objTransactions, $arrTransactionsMeta, true]);
                    } catch (IncompletePayment $exception) {
                    }
                }
            }

            $status = "past due accounts";
            foreach ($objTransactions as $objTransaction) {
                $objTransaction->update(["transaction_status" => $status]);
            }
            $account->update(["flag_status" => $status]);
            dispatch(new ServiceLedger(
                $account,
                ServiceLedgerService::STATUS_CHANGE_EVENT,
                [
                    "remote_addr" => request()->getClientIp(),
                    "remote_host" => gethostbyaddr(request()->getClientIp()),
                    "remote_agent" => request()->server("HTTP_USER_AGENT")
                ]
            ))->onQueue("ledger");

            return ([$objTransactions, $arrTransactionsMeta, false]);
        } catch (\Exception $exception) {
            throw new PaymentTaskException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function accountPlanCharge(Account $account, AccountTransaction $objTransaction, float $planCost, ?PaymentMethod $paymentMethod = null): bool{
        try {
            if ($planCost == 0.0) {
                $objTransaction->update(["transaction_status" => "paid"]);
                return true;
            }

            [$allMethods, $user] = $this->preparePaymentMethods($account, $paymentMethod);

            if (!empty($allMethods)) {
                foreach ($allMethods as $objMethod) {
                    try {
                        $payment = $user->charge($planCost * 100, $objMethod->id);
                        $this->accountInvoiceRepo->storeInvoice($objTransaction, $planCost, $payment);
                        $objTransaction->update(["transaction_status" => "paid"]);

                        return true;
                    } catch (IncompletePayment $exception) {}
                }
            }

            $status = "past due accounts";
            $objTransaction->update(["transaction_status" => $status]);
            $account->update(["flag_status" => $status]);
            dispatch(new ServiceLedger(
                $account,
                ServiceLedgerService::STATUS_CHANGE_EVENT,
                [
                    "remote_addr" => request()->getClientIp(),
                    "remote_host" => gethostbyaddr(request()->getClientIp()),
                    "remote_agent" => request()->server("HTTP_USER_AGENT")
                ]
            ))->onQueue("ledger");

            return false;
        } catch (\Exception $exception) {
            throw new PaymentTaskException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    private function preparePaymentMethods(Account $account, ?PaymentMethod $paymentMethod = null){
        if (is_null($paymentMethod)) {
            $user = $account->user;
            $stripePaymentMethod = $user->defaultPaymentMethod();

            if (is_null($stripePaymentMethod)) {
                $allMethods = [];
            } else {
                $allMethods = [$stripePaymentMethod];
            }
        } else {
            $allMethods = [$paymentMethod];
            $user = $paymentMethod->owner();
        }

        return ([$allMethods, $user]);
    }

    /**
     * @param Account $account
     * @return array
     */
    private function prepareCharges(Account $account){
        $arrTransactions = [];
        $objSoundblockHelper = resolve(SoundblockHelper::class);
        $objAccountPlan = $account->plans()->where("flag_active", true)->orderBy("version", "desc")->first();

        /* Calculate Additional Users */
        $usersQuantity = $this->accountRepository->accountUsersCountWithoutDeleted($account);
        $additionalUsers = $usersQuantity - $objAccountPlan->planType->plan_users;

        if ($additionalUsers > 0) {
            if ($additionalUsers == 1) {
                $arrTransactions[] = Charge::chargeAccount($objAccountPlan, "user",  $objAccountPlan->planType->plan_user_additional);
            } else {
                $toPayUser = (intval($additionalUsers) - 1) * $objAccountPlan->planType->plan_user_additional;
                $arrTransactions[] = Charge::chargeAccount($objAccountPlan, "user", $toPayUser);
            }
        }

        $dateEnd = Carbon::now()->endOfDay()->toDateTimeString();
        $dateStart = Carbon::now()->subMonth()->startOfDay()->toDateTimeString();

        /* Calculate Transfer Data from Bandwidth */
        $objUsedTransfer = $this->projectsBandwidthRepo->getTransferSizeByAccountAndDates($account, $dateStart, $dateEnd);
        $freeSize = $objAccountPlan->planType->plan_bandwidth;
        $paidSize = ($objUsedTransfer - (intval($freeSize) * 1e+9)) / 1e+9;

        if ($paidSize > 0) {
            $toPayBandwidth = (intval($paidSize) * $objAccountPlan->planType->plan_bandwidth_additional);
            $arrTransactions[] = Charge::chargeAccount($objAccountPlan, "bandwidth", $toPayBandwidth);
        }

        /* Calculate DiskSpace Size */
        $objUsedDiscSpace = $objSoundblockHelper->account_directory_size($objAccountPlan->account);
        $freeDiscSpaceSize = $objAccountPlan->planType->plan_diskspace;
        $paidDiskSpaceSize = ($objUsedDiscSpace - (intval($freeDiscSpaceSize) * 1e+9)) / 1e+9;

        if ($paidDiskSpaceSize > 0) {
            $toPayDiskSpace = (intval($paidDiskSpaceSize) * $objAccountPlan->planType->plan_diskspace_additional);
            $arrTransactions[] = Charge::chargeAccount($objAccountPlan, "diskspace", $toPayDiskSpace);
        }

        return ([$arrTransactions, ["users" => $additionalUsers, "bandwidth" => $paidSize, "diskspace" => $paidDiskSpaceSize]]);
    }

    /**
     * @param Account $account
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function calculateAmount(Account $account) {
        return (
            $account->transactions()
                ->where("transaction_status", "!=", "paid")
                ->whereIn("transaction_type", ["user", "bandwidth", "diskspace"])
                ->get()
        );
    }
}
