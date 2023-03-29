<?php

namespace App\Rules\Soundblock\Collection;

use Illuminate\Contracts\Validation\Rule;

class FileThrowException implements Rule
{

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($value == "soundblock-force-failure") {
            return (false);
        }

        return (true);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "File name force exception.";
    }
}
