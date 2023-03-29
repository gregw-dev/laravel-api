<?php

namespace App\Rules\Soundblock\Project;

use Illuminate\Contracts\Validation\Rule;
use App\Repositories\Soundblock\Platform as PlatformRepo;

class CreateDeployment implements Rule
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
        $objPlatformsRepo = resolve(PlatformRepo::class);
        $objYoutubePlatform = $objPlatformsRepo->findByName("Youtube Music");

        if (in_array($objYoutubePlatform->platform_uuid, $this->arrRequest["platforms"])) {
            if ($this->arrRequest["youtube_agreement"]) {
                return (true);
            }

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
        return "Youtube Music agreement is not confirmed.";
    }
}
