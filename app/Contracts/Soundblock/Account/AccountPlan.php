<?php

namespace App\Contracts\Soundblock\Account;

use App\Models\Users\User;
use App\Models\Soundblock\Accounts\Account;
use App\Models\Soundblock\Data\PlansType as PlansTypeModel;
use App\Models\Soundblock\Accounts\AccountPlan as AccountPlanModel;

interface AccountPlan {
    public function find($id, bool $bnFailure = true) : ?AccountPlanModel;

    public function cancel(User $user);
    public function create(Account $objAccount, PlansTypeModel $objPlanType): AccountPlanModel;
    public function update(Account $objAccount, PlansTypeModel $objPlanType, AccountPlanModel $objOldPlan): Account;
}
