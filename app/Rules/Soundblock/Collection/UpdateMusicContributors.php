<?php

namespace App\Rules\Soundblock\Collection;

use Illuminate\Contracts\Validation\Rule;
use App\Repositories\Soundblock\Data\Contributors as ContributorsRepository;

class UpdateMusicContributors implements Rule
{
    private array $requestData;

    /**
     * Create a new rule instance.
     *
     * @param array $arrAllData
     */
    public function __construct(array $arrAllData)
    {
        $this->requestData = $arrAllData;
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
        $objContributorsTypesRepo = resolve(ContributorsRepository::class);
        $strComposerUuid = $objContributorsTypesRepo->getComposerUuid();
        $flagComposer = false;

        foreach ($this->requestData["contributors"] as $arrContributor) {
            if(in_array($strComposerUuid, $arrContributor["types"])){
                $flagComposer = true;
            }
        }

        return ($flagComposer);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "Track must have composer.";
    }
}
