<?php

namespace App\Rules\Soundblock\Collection;

use Illuminate\Contracts\Validation\Rule;
use App\Repositories\Soundblock\Project as ProjectRepo;
use App\Repositories\Soundblock\Collection as CollectionRepo;

class AddDirectory implements Rule
{
    private array $arrRequests;

    /**
     * Create a new rule instance.
     *
     * @param array $arrRequests
     */
    public function __construct(array $arrRequests)
    {
        $this->arrRequests = $arrRequests;
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
        $objProjectRepo = resolve(ProjectRepo::class);
        $objCollectionRepo = resolve(CollectionRepo::class);

        $objProject = $objProjectRepo->find($this->arrRequests["project"]);
        $objLatestCol = $objCollectionRepo->findLatestByProject($objProject);

        return (
            !$objLatestCol->directories()
                ->where("directory_path", $this->arrRequests["directory_path"])
                ->where("directory_name", $this->arrRequests["directory_name"])
                ->exists()
        );
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "Duplicate directory.";
    }
}
