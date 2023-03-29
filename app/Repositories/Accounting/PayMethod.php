<?php

namespace App\Repositories\Accounting;

use App\Models\Users\Accounting\AccountingPayMethods;
use App\Repositories\BaseRepository;

class PayMethod extends BaseRepository {

    protected \Illuminate\Database\Eloquent\Model $model;

    public function __construct(AccountingPayMethods $objPayMethod) {
        $this->model = $objPayMethod;
    }
}
