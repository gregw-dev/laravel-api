<?php

namespace App\Services\Soundblock\Contributor;

use Exception;
use Util;
use App\Models\Soundblock\Contributor as ContributorModel;
use App\Models\Soundblock\Accounts\Account as AccountModel;
use App\Repositories\Soundblock\Contributor as ContributorRepository;
use App\Contracts\Soundblock\Contributor\Contributor as ContributorContract;

class Contributor implements ContributorContract {
    /** @var ContributorRepository */
    private ContributorRepository $contributorRepo;

    /**
     * Contributor constructor.
     * @param ContributorRepository $contributorRepo
     */
    public function __construct(ContributorRepository $contributorRepo) {
        $this->contributorRepo = $contributorRepo;
    }

    /**
     * @param string $strAccountUuid
     * @return mixed
     */
    public function findAllByAccount(string $strAccountUuid){
        $objContributors = $this->contributorRepo->findAllByAccount($strAccountUuid);
        $objContributors->map(function($objContributor) {
            $flagTracks = false;

            if ($objContributor->tracks->count() > 0) {
                $flagTracks = true;
            }

            $objContributor->flag_tracks = $flagTracks;
            unset($objContributor["tracks"]);

            return ($objContributor);
        });

        return ($objContributors);
    }

    /**
     * @param string $strContributorUuid
     * @return mixed
     */
    public function find(string $strContributorUuid){
        return ($this->contributorRepo->find($strContributorUuid));
    }

    /**
     * @param string $strName
     * @return mixed
     */
    public function findByName(string $strName) {
        return ($this->contributorRepo->findByName($strName));
    }

    /**
     * @param array $arrData
     * @return mixed
     */
    public function typeahead(array $arrData) {
        return ($this->contributorRepo->typeahead($arrData));
    }

    /**
     * @param array $arrData
     * @param AccountModel $objAccount
     * @return mixed
     * @throws \Exception
     */
    public function create(array $arrData, AccountModel $objAccount) {
        $objContributor = $this->contributorRepo->findByAccountAndName($objAccount->account_uuid, $arrData["contributor_name"]);

        if ($objContributor) {
            throw new Exception("You are already have this contributor.");
        }

        $arrData["contributor_uuid"] = Util::uuid();
        $arrData["account_id"] = $objAccount->account_id;
        $arrData["account_uuid"] = $objAccount->account_uuid;

        return ($this->contributorRepo->create($arrData));
    }

    /**
     * @param ContributorModel $objContributor
     * @param array $arrUpdateData
     * @return mixed
     */
    public function update(ContributorModel $objContributor, array $arrUpdateData){
        return ($this->contributorRepo->update($objContributor, $arrUpdateData));
    }

    /**
     * @param string $strContributorUuid
     * @return mixed
     */
    public function delete(string $strContributorUuid){
        return ($this->contributorRepo->delete($strContributorUuid));
    }

    /**
     * @param string $strContributorUuid
     * @return mixed
     */
    public function setFlagPermanent(string $strContributorUuid, bool $boolFlag){
        return ($this->contributorRepo->setFlagPermanent($strContributorUuid, $boolFlag));
    }
}
