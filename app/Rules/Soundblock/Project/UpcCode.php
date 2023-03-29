<?php

namespace App\Rules\Soundblock\Project;

use App\Contracts\Soundblock\Data\UpcCodes;
use Illuminate\Contracts\Validation\Rule;

class UpcCode implements Rule
{

    /**
     * Create a new rule instance.
     *
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
        $upcService = resolve(UpcCodes::class);

        if ($attribute == "project_upc") {
            $boolResult = false;

            if (strlen($value) == 12) {
                $upc = substr($value, 0, -1);
                $lastNum = $value[strlen($value)-1];

                $oddVal = 0;
                $evenVal = 0;

                for ($i = 0; $i < strlen($upc); $i++) {
                    if ($i % 2 == 0) {
                        $oddVal += intval($upc[$i]);
                    } else {
                        $evenVal += intval($upc[$i]);
                    }
                }

                $oddVal *= 3;

                $sum = $oddVal + $evenVal;
                $checkNum = 10 - ($sum % 10);

                if ($checkNum == 10) {
                    $checkNum = 0;
                }

                if (intval($lastNum) == intval($checkNum)) {
                    $boolResult = true;
                }
            } elseif (strlen($value) == 13) {
                $boolResult = true;
            }

            $objUpc = $upcService->find($value);

            if (is_object($objUpc) && $objUpc->flag_assigned == true) {
                $boolResult = false;
            }

            return ($boolResult);
        } else {
            return (true);
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "Project UPC code is invalid.";
    }
}
