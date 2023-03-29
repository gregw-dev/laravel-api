<?php

namespace App\Rules\Soundblock\Payment;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Users\Accounting\AccountingPayMethods;
use App\Models\Users\Accounting\AccountingBanking;

class Withdrawal implements Rule
{
    private array $arrRequest;

    /**
     * Create a new rule instance.
     *
     * @param array $arrRequest
     */
    public function __construct(array $arrRequest)
    {
        $this->arrRequest = $arrRequest;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($this->arrRequest["withdrawal_method"] == "banking") {
            return (AccountingBanking::where("row_uuid", $value)->exists());
        } elseif ($this->arrRequest["withdrawal_method"] == "paymethod") {
            return (AccountingPayMethods::where("row_uuid", $value)->exists());
        } else {
            return (false);
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "Wrong withdrawal_uuid parameter.";
    }
}
