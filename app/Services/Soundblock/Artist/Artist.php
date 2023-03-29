<?php

namespace App\Services\Soundblock\Artist;

use App\Facades\Core\Converter;
use App\Repositories\Soundblock\Contributor as ContributorRepository;
use App\Repositories\Soundblock\Project as ProjectRepository;
use App\Repositories\Soundblock\Track as TrackRepository;
use Exception;
use Illuminate\Support\Facades\Storage;
use App\Helpers\Util;
use App\Helpers\Filesystem\Soundblock;
use App\Models\Soundblock\Artist as ArtistModel;
use App\Models\Soundblock\Accounts\Account as AccountModel;
use App\Repositories\Soundblock\Artist as ArtistRepository;
use App\Contracts\Soundblock\Artist\Artist as ArtistContract;
use App\Models\Soundblock\ArtistPublisher as ArtistPublisherModel;
use App\Models\Soundblock\Projects\Deployments\Deployment as DeploymentModel;
use App\Repositories\Soundblock\ArtistPublisher as ArtistPublisherRepository;
use Illuminate\Support\Facades\DB;

class Artist implements ArtistContract {
    /** @var ArtistRepository */
    private ArtistRepository $artistRepo;
    /** @var ArtistPublisherRepository */
    private ArtistPublisherRepository $artistPublisherRepo;
    /** @var \Illuminate\Contracts\Filesystem\Filesystem|\Illuminate\Filesystem\FilesystemAdapter */
    private $soundblockAdapter;
    /** @var ContributorRepository */
    private ContributorRepository $contributorRepo;
    /** @var ProjectRepository */
    private ProjectRepository $projectRepo;
    /** @var TrackRepository */
    private TrackRepository $trackRepo;

    /**
     * Artist constructor.
     * @param ArtistRepository $artistRepo
     * @param ArtistPublisherRepository $artistPublisherRepo
     * @param ContributorRepository $contributorRepo
     * @param ProjectRepository $projectRepo
     * @param TrackRepository $trackRepo
     */
    public function __construct(ArtistRepository $artistRepo, ArtistPublisherRepository $artistPublisherRepo,
                                ContributorRepository $contributorRepo, ProjectRepository $projectRepo,
                                TrackRepository $trackRepo) {
        $this->trackRepo = $trackRepo;
        $this->artistRepo = $artistRepo;
        $this->projectRepo = $projectRepo;
        $this->contributorRepo = $contributorRepo;
        $this->artistPublisherRepo = $artistPublisherRepo;

        if (env("APP_ENV") == "local") {
            $this->soundblockAdapter = Storage::disk("local");
        } else {
            $this->soundblockAdapter = bucket_storage("soundblock");
        }
    }

    /**
     * @param string $account_uuid
     * @return mixed
     */
    public function findAllByAccount(string $account_uuid){
        $objArtists = $this->artistRepo->findAllByAccount($account_uuid);

        $objArtists->each(function ($objArtist) {
            $objArtist["avatar_url"] = $objArtist->avatar_url;
        });

        return ($objArtists);
    }

    /**
     * @param string $artist_uuid
     * @return mixed
     * @throws \Exception
     */
    public function findByUuid(string $artist_uuid){
        return ($this->artistRepo->find($artist_uuid));
    }

    public function find(string $strName) {
        return $this->artistRepo->findByName($strName);
    }

    public function findArtistPublisher(string $publisher){
        return ($this->artistPublisherRepo->find($publisher));
    }

    public function findAllPublisherByAccount(string $account){
        return ($this->artistPublisherRepo->findAllByAccount($account));
    }

    public function typeahead(array $arrData) {
        return $this->artistRepo->typeahead($arrData);
    }

    public function create(array $arrData, AccountModel $objAccount) {
        $objArtist = $this->artistRepo->findByAccountAndName($objAccount->account_uuid, $arrData["artist_name"]);

        if ($objArtist) {
            throw new Exception("This artist has already been added.");
        }

        $arrData["artist_uuid"] = Util::uuid();
        $arrData["account_id"] = $objAccount->account_id;
        $arrData["account_uuid"] = $objAccount->account_uuid;

        if (isset($arrData["url_apple"]) && $arrData["url_apple"] != "") {
            $arrParsedUrlApple = parse_url($arrData["url_apple"]);

            if (
                empty($arrParsedUrlApple["scheme"]) ||
                empty($arrParsedUrlApple["host"]) ||
                (strpos($arrParsedUrlApple["host"], "music.apple.com") === false) ||
                empty($arrParsedUrlApple["path"]) ||
                (strpos($arrParsedUrlApple["path"], "/artist/") === false)
            ) {
                throw new Exception("Invalid apple music url.");
            }

            $arrData["url_apple"] = "https://" . $arrParsedUrlApple["host"]  . $arrParsedUrlApple["path"];
        }

        if (isset($arrData["url_spotify"]) && $arrData["url_spotify"] != "") {
            $arrParsedUrlSpotify = parse_url($arrData["url_spotify"]);

            if (
                empty($arrParsedUrlSpotify["scheme"]) ||
                empty($arrParsedUrlSpotify["host"]) ||
                (strpos($arrParsedUrlSpotify["host"], "open.spotify.com") === false) ||
                empty($arrParsedUrlSpotify["path"]) ||
                (strpos($arrParsedUrlSpotify["path"], "/artist/") === false)
            ) {
                throw new Exception("Invalid spotify url.");
            }

            $arrData["url_spotify"] = "https://" . $arrParsedUrlSpotify["host"]  . $arrParsedUrlSpotify["path"];
        }

        if (isset($arrData["url_soundcloud"]) && $arrData["url_soundcloud"] != "") {
            $arrParsedUrlSoundcloud = parse_url($arrData["url_soundcloud"]);

            if (
                empty($arrParsedUrlSoundcloud["scheme"]) ||
                empty($arrParsedUrlSoundcloud["host"]) ||
                (strpos($arrParsedUrlSoundcloud["host"], "soundcloud.com") === false) ||
                empty(str_replace("/", "", $arrParsedUrlSoundcloud["path"]))
            ) {
                throw new Exception("Invalid soundcloud url.");
            }

            $arrData["url_soundcloud"] = "https://" . $arrParsedUrlSoundcloud["host"]  . $arrParsedUrlSoundcloud["path"];
        }

        return $this->artistRepo->create($arrData);
    }

    public function uploadAvatar($objFile, ArtistModel $objArtist){
        $success = true;
        $avatarPath = Soundblock::artists_avatar_path($objArtist);
        $avatarName = $objArtist->artist_uuid . ".png";

        if ($this->soundblockAdapter->exists("public/" . $avatarPath . "/" . $avatarName)) {
            $this->soundblockAdapter->delete("public/" . $avatarPath . "/" . $avatarName);
        }

        $ext = $objFile->getClientOriginalExtension();

        if (Util::lowerLabel($ext) !== "png") {
            $objFile = Converter::convertImageToPng($objFile->getPathname());
        }

        if (!$this->soundblockAdapter->putFileAs("public/" . $avatarPath, $objFile, $avatarName, ["visibility" => "public"])) {
            $success = false;
        }

        $objArtist->update(['artist_avatar_rand' => mt_rand()]);

        return ($success);
    }

    public function uploadDraftAvatar($objFile, ArtistModel $objArtist){
        $success = true;
        $avatarPath = "upload/artists";
        $avatarName = $objArtist->artist_uuid . ".png";

        if ($this->soundblockAdapter->exists("public/" . $avatarPath . "/" . $avatarName)) {
            $this->soundblockAdapter->delete("public/" . $avatarPath . "/" . $avatarName);
        }

        $ext = $objFile->getClientOriginalExtension();

        if (Util::lowerLabel($ext) !== "png") {
            $objFile = Converter::convertImageToPng($objFile->getPathname());
        }

        if (!$this->soundblockAdapter->putFileAs("public/" . $avatarPath, $objFile, $avatarName, ["visibility" => "public"])) {
            $success = false;
        }

        $objArtist->update(['artist_avatar_rand' => mt_rand()]);

        return ($success);
    }

    public function storeArtistPublisher(string $strName, AccountModel $objAccount, ArtistModel $objArtist){
        $objPublisher = $this->artistPublisherRepo->findByAccountAndName($objAccount->account_uuid, $strName);

        if ($objPublisher) {
            throw new Exception("You are already have this publisher.");
        }

        $arrParams = [
            "publisher_uuid" => Util::uuid(),
            "account_id" => $objAccount->account_id,
            "account_uuid" => $objAccount->account_uuid,
            "artist_id" => $objArtist->artist_id,
            "artist_uuid" => $objArtist->artist_uuid,
            "publisher_name" => $strName
        ];

        return ($this->artistPublisherRepo->create($arrParams));
    }

    public function update(ArtistModel $objArtist, array $updateData){
        $strArtistName = $updateData["artist_name"];
        $objArtistCheck = DB::table('soundblock_artists')
        ->where("account_uuid",$objArtist->account_uuid)
        ->where(DB::raw('lower(artist_name)'),  strtolower($strArtistName))
        ->first();
        if ($objArtistCheck && $objArtistCheck->artist_uuid !== $objArtist->artist_uuid) {
            throw new Exception("Another artist on this account already exists with same artist name");
        }
        if (isset($updateData["url_apple"]) && $updateData["url_apple"] != "") {
            $arrParsedUrlApple = parse_url($updateData["url_apple"]);

            if (
                empty($arrParsedUrlApple["scheme"]) ||
                empty($arrParsedUrlApple["host"]) ||
                (strpos($arrParsedUrlApple["host"], "music.apple.com") === false) ||
                empty($arrParsedUrlApple["path"]) ||
                (strpos($arrParsedUrlApple["path"], "/artist/") === false)
            ) {
                throw new Exception("Invalid apple music url.");
            }

            $updateData["url_apple"] = "https://" . $arrParsedUrlApple["host"]  . $arrParsedUrlApple["path"];
        }

        if (isset($updateData["url_spotify"]) && $updateData["url_spotify"] != "") {
            $arrParsedUrlSpotify = parse_url($updateData["url_spotify"]);

            if (
                empty($arrParsedUrlSpotify["scheme"]) ||
                empty($arrParsedUrlSpotify["host"]) ||
                (strpos($arrParsedUrlSpotify["host"], "open.spotify.com") === false) ||
                empty($arrParsedUrlSpotify["path"]) ||
                (strpos($arrParsedUrlSpotify["path"], "/artist/") === false)
            ) {
                throw new Exception("Invalid spotify url.");
            }

            $updateData["url_spotify"] = "https://" . $arrParsedUrlSpotify["host"]  . $arrParsedUrlSpotify["path"];
        }

        if (isset($updateData["url_soundcloud"]) && $updateData["url_soundcloud"] != "") {
            $arrParsedUrlSoundcloud = parse_url($updateData["url_soundcloud"]);

            if (
                empty($arrParsedUrlSoundcloud["scheme"]) ||
                empty($arrParsedUrlSoundcloud["host"]) ||
                (strpos($arrParsedUrlSoundcloud["host"], "soundcloud.com") === false) ||
                empty(str_replace("/", "", $arrParsedUrlSoundcloud["path"]))
            ) {
                throw new Exception("Invalid soundcloud url.");
            }

            $updateData["url_soundcloud"] = "https://" . $arrParsedUrlSoundcloud["host"]  . $arrParsedUrlSoundcloud["path"];
        }

        return ($this->artistRepo->update($objArtist, $updateData));
    }

    public function updateArtistPublisher(ArtistPublisherModel $objPublisher, string $name){
        return ($this->artistPublisherRepo->update($objPublisher, ["publisher_name" => $name]));
    }

    public function delete(string $artist){
        return ($this->artistRepo->delete($artist));
    }

    public function deleteArtistPublisher(string $publisher){
        return ($this->artistPublisherRepo->destroy($publisher));
    }

    public function setFlagPermanent(string $strArtist, bool $boolFlag){
        return ($this->artistRepo->setFlagPermanent($strArtist, $boolFlag));
    }

    public function unsetFlagPermanentByDeployment(DeploymentModel $objDeployment){
        $objTracksArtists = collect();
        $objTracksContributors = collect();
        $deploymentStatus = ["pending", "deployed"];

        $objCollection = $objDeployment->collection;
        $objProject = $objCollection->project;
        $objProjectArtists = $objProject->artists;

        foreach ($objCollection->tracks as $objTrack) {
            $objTracksArtists->push($objTrack->artists);
            $objTracksContributors->push($objTrack->contributors);
        }

        $objTracksArtists = $objTracksArtists->flatten(1);
        $objContributors = $objTracksContributors->flatten(1);

        $objArtists = $objTracksArtists->merge($objProjectArtists)->unique("artist_uuid");
        $objContributors = $objContributors->unique("contributor_uuid");

        if ($objArtists->count() > 0) {
            foreach ($objArtists as $objArtist) {
                $boolFlagPermanent = false;
                $objProjects = $this->projectRepo->getAllWhereArtist($objArtist->artist_uuid);
                $objTracks = $this->trackRepo->getAllWhereArtist($objArtist->artist_uuid);
                $objTracks = $objTracks->unique("track_uuid");

                if ($objTracks->count() > 0) {
                    foreach ($objTracks as $objTrack) {
                        $objCollections = $objTrack->collections;

                        foreach ($objCollections as $objCollection) {
                            $objProjects->push($objCollection->project);
                        }
                    }
                }

                $objProjects = $objProjects->unique("project_uuid");

                if ($objProjects->count() > 0) {
                    foreach ($objProjects as $objProject) {
                        $objDeployments = $objProject->deployments;

                        if ($objDeployments->count() > 0) {
                            foreach ($objDeployments as $objDeployment) {
                                if (in_array(strtolower($objDeployment->deployment_status), $deploymentStatus)) {
                                    $boolFlagPermanent = true;
                                }
                            }
                        }
                    }
                }

                if (!$boolFlagPermanent) {
                    $this->artistRepo->setFlagPermanent($objArtist->artist_uuid, false);
                }
            }
        }

        if ($objContributors->count() > 0) {
            foreach ($objContributors as $objContributor) {
                $boolFlagPermanent = false;
                $objProjects = collect();
                $objTracks = $this->trackRepo->getAllWhereContributor($objContributor["contributor_uuid"]);
                $objTracks = $objTracks->unique("track_uuid");

                if ($objTracks->count() > 0) {
                    foreach ($objTracks as $objTrack) {
                        $objCollections = $objTrack->collections;

                        foreach ($objCollections as $objCollection) {
                            $objProjects->push($objCollection->project);
                        }
                    }
                }

                $objProjects = $objProjects->unique("project_uuid");

                if ($objProjects->count() > 0) {
                    foreach ($objProjects as $objProject) {
                        $objDeployments = $objProject->deployments;

                        if ($objDeployments->count() > 0) {
                            foreach ($objDeployments as $objDeployment) {
                                if (in_array(strtolower($objDeployment->deployment_status), $deploymentStatus)) {
                                    $boolFlagPermanent = true;
                                }
                            }
                        }
                    }
                }

                if (!$boolFlagPermanent) {
                    $this->contributorRepo->setFlagPermanent($objContributor->contributor_uuid, false);
                }
            }
        }

        return (true);
    }
}
