<?php

namespace App\Rules\Core\Support;

use Illuminate\Contracts\Validation\Rule;

class FileMimesRule implements Rule
{
    const ALLOWED_MIMES = ["png","jpg","bmp","tiff","jpeg","txt","pdf","doc","docx","avi","mov","wmv","webm","m4v","mp4","wav","mp3"];
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
        if (is_array($value)) {
            foreach ($value as $file) {
                if (in_array($file->getClientOriginalExtension(), self::ALLOWED_MIMES)) {
                    return (true);
                }
            }
        } else {
            if (in_array($value->getClientOriginalExtension(), self::ALLOWED_MIMES)) {
                return (true);
            }
        }

        return (false);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        $arrMimes = self::ALLOWED_MIMES;
        asort($arrMimes);

        return "Wrong file extension, allowed extensions: " . implode(", ", $arrMimes);
    }
}
