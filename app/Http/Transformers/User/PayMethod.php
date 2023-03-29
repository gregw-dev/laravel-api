<?php

namespace App\Http\Transformers\User;

use App\Http\Transformers\BaseTransformer;
use App\Models\Users\Accounting\AccountingPayMethods;
use App\Traits\StampCache;

class PayMethod extends BaseTransformer
{

    use StampCache;

    public function transform(AccountingPayMethods $objPayMethod)
    {
        $response = [
            "pay_method_uuid" => $objPayMethod->row_uuid,
            "pay_method" => $objPayMethod->paymethod_account,
            "flag_primary" => $objPayMethod->flag_primary,
        ];
        $response = array_merge($response, $this->stamp($objPayMethod));

        return($response);
    }
}
