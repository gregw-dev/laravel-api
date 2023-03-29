<?php

namespace App\Services\Soundblock;

use App\Repositories\{
    Accounting\AccountingType as AccountingTypeRepository,
    Soundblock\AccountTransaction as AccountTransactionRepository
};

class AccountTransaction {
    /** @var AccountTransactionRepository */
    protected AccountTransactionRepository $accountTransactionRepo;
    /** @var AccountingTypeRepository */
    protected AccountingTypeRepository $accountingTypeRepo;

    public function __construct(AccountTransactionRepository $accountTransactionRepo, AccountingTypeRepository $accountingTypeRepo) {
        $this->accountingTypeRepo     = $accountingTypeRepo;
        $this->accountTransactionRepo = $accountTransactionRepo;
    }
}
