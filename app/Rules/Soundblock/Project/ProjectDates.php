<?php

namespace App\Rules\Soundblock\Project;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;

class ProjectDates implements Rule
{
    private $arrRequest;

    /**
     * Create a new rule instance.
     *
     * @param $arrRequest
     */
    public function __construct($arrRequest)
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
        $objProjectPreReleaseDate = Carbon::parse($value);
        $objProjectReleaseDate = Carbon::parse($this->arrRequest["project_date"]);

        if ($objProjectPreReleaseDate->equalTo($objProjectReleaseDate) || $objProjectPreReleaseDate->greaterThan($objProjectReleaseDate)) {
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
        return "Project pre-release date must be earlier than release date.";
    }
}
