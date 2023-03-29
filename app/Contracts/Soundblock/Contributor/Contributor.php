<?php

namespace App\Contracts\Soundblock\Contributor;

use App\Models\Soundblock\Contributor as ContributorModel;
use App\Models\Soundblock\Accounts\Account as AccountModel;

interface Contributor {
    public function find(string $strContributorUuid);
    public function findByName(string $strName);
    public function findAllByAccount(string $strAccountUuid);
    public function create(array $arrData, AccountModel $objAccount);
    public function typeahead(array $arrData);
    public function update(ContributorModel $objContributor, array $arrUpdateData);
    public function delete(string $strContributorUuid);
    public function setFlagPermanent(string $strContributorUuid, bool $boolFlag);
}
