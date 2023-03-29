<?php

namespace App\Rules\Soundblock\Collection;

use Illuminate\Contracts\Validation\Rule;
use App\Repositories\Soundblock\Data\Contributors as ContributorsRepository;

class UploadMusicContributors implements Rule
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
        $flagComposer = false;

        if ($this->requestData["files"][0]["is_zip"] == 1) {
            $arrMusicFiles = [];

            foreach ($this->requestData["files"][0]["zip_content"] as $arrFile) {
                if ($arrFile["file_category"] == "music") {
                    array_push($arrMusicFiles, $arrFile);
                }
            }

            if (!empty($arrMusicFiles)) {
                $objContributorsTypesRepo = resolve(ContributorsRepository::class);
                $strComposerUuid = $objContributorsTypesRepo->getComposerUuid();

                foreach ($arrMusicFiles as $arrFile) {
                    foreach ($arrFile["contributors"] as $arrContributor) {
                        if(in_array($strComposerUuid, $arrContributor["types"])){
                            $flagComposer = true;
                        }
                    }
                }
            }
        } else {
            $arrMusicFiles = [];

            foreach ($this->requestData["files"] as $arrFile) {
                if ($arrFile["file_category"] == "music") {
                    array_push($arrMusicFiles, $arrFile);
                }
            }

            if (!empty($arrMusicFiles)) {
                $objContributorsTypesRepo = resolve(ContributorsRepository::class);
                $strComposerUuid = $objContributorsTypesRepo->getComposerUuid();

                foreach ($arrMusicFiles as $arrFile) {
                    foreach ($arrFile["contributors"] as $arrContributor) {
                        if(in_array($strComposerUuid, $arrContributor["types"])){
                            $flagComposer = true;
                        }
                    }
                }
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
        return "Each track must have composer.";
    }
}
