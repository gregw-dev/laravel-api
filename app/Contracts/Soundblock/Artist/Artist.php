<?php

namespace App\Contracts\Soundblock\Artist;

use App\Models\Soundblock\Artist as ArtistModel;
use App\Models\Soundblock\Accounts\Account as AccountModel;
use App\Models\Soundblock\ArtistPublisher as ArtistPublisherModel;
use App\Models\Soundblock\Projects\Deployments\Deployment as DeploymentModel;

interface Artist {
    public function find(string $strName);
    public function findByUuid(string $artist_uuid);
    public function findAllByAccount(string $account_uuid);
    public function create(array $arrData, AccountModel $objAccount);
    public function uploadAvatar($objFile, ArtistModel $objArtist);
    public function uploadDraftAvatar($objFile, ArtistModel $objArtist);
    public function typeahead(array $arrData);
    public function update(ArtistModel $objArtist, array $updateData);
    public function delete(string $artist);
    public function findArtistPublisher(string $publisher);
    public function findAllPublisherByAccount(string $account);
    public function storeArtistPublisher(string $strName, AccountModel $objAccount, ArtistModel $objArtist);
    public function updateArtistPublisher(ArtistPublisherModel $objPublisher, string $name);
    public function deleteArtistPublisher(string $publisher);
    public function setFlagPermanent(string $strArtist, bool $boolFlag);
    public function unsetFlagPermanentByDeployment(DeploymentModel $objDeployment);
}
