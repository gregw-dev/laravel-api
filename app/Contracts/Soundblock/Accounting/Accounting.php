<?php

namespace App\Contracts\Soundblock\Accounting;

use App\Models\Soundblock\Accounts\AccountTransaction;
use App\Models\Users\User;
use Laravel\Cashier\PaymentMethod;
use App\Models\Soundblock\Accounts\Account;

interface Accounting {
    public function makeCharge(Account $account, ?PaymentMethod $paymentMethod = null): array;

    public function chargePastDueAccount(Account $objAccount, PaymentMethod $paymentMethod): bool;

    public function accountPlanCharge(Account $account, AccountTransaction $objTransaction, float $planCost, ?PaymentMethod $paymentMethod = null): bool;

    public function chargeUserImmediately(User $user, PaymentMethod $paymentMethod): bool;
}
