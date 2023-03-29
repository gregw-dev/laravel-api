<?php

namespace App\Rules\Soundblock\Conference;

use Illuminate\Contracts\Validation\Rule;
use Auth;

class IsValidProjectUuid implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        $arrAccountUuid = Auth::user()->accounts->pluck('account_uuid')->toArray();
        $arrProjectUuid = \App\Models\Soundblock\Projects\Project::whereIn('account_uuid',$arrAccountUuid)
            ->pluck('project_uuid')
            ->toArray();
        if(!in_array($value, $arrProjectUuid)){
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
        return 'project_uuid field is invalid or unauthorized';
    }
}
