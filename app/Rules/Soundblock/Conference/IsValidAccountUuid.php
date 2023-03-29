<?php

namespace App\Rules\Soundblock\Conference;

use Illuminate\Contracts\Validation\Rule;
use Auth;

class IsValidAccountUuid implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {

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
        $arrTemp = Auth::user()->accounts->pluck('account_uuid')->toArray();
        if(!in_array($value, $arrTemp)){
            return false;
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'account_uuid field is invalid or unauthorized';
    }
}
