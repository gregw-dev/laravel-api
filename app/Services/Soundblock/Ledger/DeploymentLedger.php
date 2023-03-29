<?php

namespace App\Services\Soundblock\Ledger;

use App\Contracts\Soundblock\Ledger\BaseLedger;
use App\Services\Soundblock\Ledger\BaseLedger as BaseLedgerService;

class DeploymentLedger extends BaseLedgerService implements BaseLedger {
    const QLDB_TABLE = "deployments";

    const CANCELED          = "Canceled";
    const CHANGE_COLLECTION = "Change Collection";
    const DEPLOYED          = "Deployed";
    const FAILED            = "Failed";
    const NEW_DEPLOYMENT    = "New Deployment";
    const PENDING           = "Pending";
    const TAKE_DOWN         = "Take Down";
    const REMOVED           = "Removed";


}
