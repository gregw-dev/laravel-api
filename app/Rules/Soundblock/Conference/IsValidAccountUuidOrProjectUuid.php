<?php

namespace App\Rules\Soundblock\Conference;

use Illuminate\Contracts\Validation\Rule;
use Auth;

class IsValidAccountUuidOrProjectUuid implements Rule
{
    protected string $room_type;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($room_type)
    {
        $this->room_type = strtolower($room_type);
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
        if($this->room_type === 'account'){
            $arrTemp = Auth::user()->accounts->pluck('account_uuid')->toArray();
        }else{
            $arrTemp = Auth::user()->teams->pluck('project_uuid')->toArray();
        }
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
        return "Invalid or unauthorized uuid";
    }
}
