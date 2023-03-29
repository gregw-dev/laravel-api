<?php

namespace App\Rules\Office\Reports;

use Illuminate\Contracts\Validation\Rule;
use App\Repositories\Soundblock\Data\PlatformReportMetadata;
use App\Models\Soundblock\Reports\Music as ProcessedMusicFiles;

class UploadReportFile implements Rule
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
        if ($value->extension() == "zip") {
            return (true);
        }
        $objPlatformsRepo = resolve(PlatformReportMetadata::class);
        $strFileName = $value->getClientOriginalName();

        if (!$objPlatformsRepo->canProcessFile($strFileName) || ProcessedMusicFiles::where("file_path", $strFileName)->exists()) {
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
        return "Report file already processed.";
    }
}
