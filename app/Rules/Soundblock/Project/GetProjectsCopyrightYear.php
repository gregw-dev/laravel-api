<?php

namespace App\Rules\Soundblock\Project;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;

class GetProjectsCopyrightYear implements Rule
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
        try {
            $objCarbonEndDate = Carbon::createFromFormat("Y", $value);
            $objCarbonStartsDate = Carbon::createFromFormat("Y", $this->arrRequest["copyright_year_starts"]);
        } catch (\Exception $e) {
            return false;
        }

        if ($objCarbonEndDate->greaterThanOrEqualTo($objCarbonStartsDate)) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "Copyright year ends must be greater or equal to copyright year starts.";
    }
}
