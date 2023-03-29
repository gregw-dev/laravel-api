<?php

namespace App\Rules\Soundblock\Collection;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;
use App\Repositories\Soundblock\Project as ProjectRepository;

class TrackReleaseDate implements Rule
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
        $objProjectRepo = resolve(ProjectRepository::class);
        $objProject = $objProjectRepo->find($this->arrRequest["project"]);
        $strDate = isset($objProject->date_release_pre) ? $objProject->date_release_pre : $objProject->project_date;
        $carbonTrackDate = Carbon::parse($value);

        return ($carbonTrackDate->greaterThanOrEqualTo($strDate));
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "Track release date can't be earlier than project date.";
    }
}
