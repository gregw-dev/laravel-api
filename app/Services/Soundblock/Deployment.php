<?php

namespace App\Services\Soundblock;

use App\Helpers\Util;
use App\Helpers\Filesystem\Soundblock;
use App\Events\Office\UpdateDeployment;
use App\Services\Common\Zip as ZipService;
use App\Events\Soundblock\DeploymentHistory;
use App\Jobs\Soundblock\Ledger\DeploymentLedger;
use App\Services\Soundblock\Project as ProjectService;
use App\Events\Soundblock\Deployment as DeploymentMail;
use App\Contracts\Soundblock\Artist\Artist as ArtistService;
use App\Services\Soundblock\Ledger\DeploymentLedger as DeploymentLedgerService;
use App\Models\{
    Soundblock\Projects\Deployments\Deployment as DeploymentModel,
    Soundblock\Projects\Deployments\DeploymentStatus,
    Soundblock\Projects\Project,
    Soundblock\Collections\Collection as CollectionModel
};
use App\Repositories\{
    Common\Notification,
    Soundblock\Collection,
    Soundblock\Deployment as DeploymentRepository,
    Soundblock\DeploymentStatus as DeploymentStatusRepository,
    Soundblock\Project as ProjectRepository,
    Soundblock\Artist as ArtistRepository,
    Soundblock\Contributor as ContributorRepository,
    Soundblock\Platform,
    User\User as UserRepository,
    Common\QueueJob as QueueJobRepository,
    Soundblock\Data\Contributors as ContributorsRolesRepository,
    Soundblock\DeploymentTakedown as DeploymentTakedownRepository
};

class Deployment {

    protected int $PROCESSING = 0;
    protected int $DEPLOY = 1;

    protected Collection $colRepo;
    protected Platform $platformRepo;
    protected Notification $notiRepo;
    protected ZipService $zipService;
    protected UserRepository $userRepo;
    protected ProjectRepository $projectRepo;
    protected ArtistRepository $artistRepo;
    protected ContributorRepository $contributorRepo;
    protected QueueJobRepository $queueJobRepo;
    protected DeploymentRepository $deploymentRepo;
    protected DeploymentStatusRepository $statusRepo;
    /** @var ArtistService */
    private ArtistService $artistService;
    /** @var ContributorsRolesRepository */
    private ContributorsRolesRepository $contributorsRolesRepo;
    private \App\Services\Soundblock\Project $projectService;
    /** @var DeploymentTakedownRepository */
    private DeploymentTakedownRepository $deploymentTakedownRepo;

    /**
     * Deployment constructor.
     * @param DeploymentRepository $deploymentRepo
     * @param DeploymentStatusRepository $statusRepo
     * @param ProjectRepository $projectRepo
     * @param ArtistRepository $artistRepo
     * @param ContributorRepository $contributorRepo
     * @param Collection $colRepo
     * @param Platform $platformRepo
     * @param UserRepository $userRepo
     * @param QueueJobRepository $queueJobRepo
     * @param Notification $notiRepo
     * @param ZipService $zipService
     * @param ArtistService $artistService
     * @param ContributorsRolesRepository $contributorsRolesRepo
     * @param \App\Services\Soundblock\Project $projectService
     * @param DeploymentTakedownRepository $deploymentTakedownRepo
     */
    public function __construct(DeploymentRepository $deploymentRepo, DeploymentStatusRepository $statusRepo,
                                ProjectRepository $projectRepo, ArtistRepository $artistRepo, ContributorRepository $contributorRepo,
                                Collection $colRepo, Platform $platformRepo, UserRepository $userRepo,
                                QueueJobRepository $queueJobRepo, Notification $notiRepo, ZipService $zipService,
                                ArtistService $artistService, ContributorsRolesRepository $contributorsRolesRepo,
                                ProjectService $projectService, DeploymentTakedownRepository $deploymentTakedownRepo) {
        $this->colRepo = $colRepo;
        $this->notiRepo = $notiRepo;
        $this->userRepo = $userRepo;
        $this->zipService = $zipService;
        $this->statusRepo = $statusRepo;
        $this->projectRepo = $projectRepo;
        $this->artistRepo = $artistRepo;
        $this->contributorRepo = $contributorRepo;
        $this->platformRepo = $platformRepo;
        $this->queueJobRepo = $queueJobRepo;
        $this->deploymentRepo = $deploymentRepo;
        $this->artistService = $artistService;
        $this->contributorsRolesRepo = $contributorsRolesRepo;
        $this->projectService = $projectService;
        $this->deploymentTakedownRepo = $deploymentTakedownRepo;
    }

    /**
     * @param array $arrParams
     * @param array $arrSort
     * @return mixed
     * @throws \Exception
     */
    public function findAll(array $arrParams, array $arrSort = [], bool $bnGroupByCollection = false) {
        if (array_key_exists("statuses", $arrParams)) {
            $status = $arrParams["statuses"];
        } elseif (array_key_exists("status", $arrParams)) {
            $status = [$arrParams["status"]];
        } else {
            $status = ["Pending"];
        }

        $per_page = $arrParams["per_page"] ?? 10;

        if (isset($arrParams["user_uuids"])) {
            $arrParams["account_uuids"] = [];
            foreach ($arrParams["user_uuids"] as $user_uuid) {
                $objUser = $this->userRepo->find($user_uuid);
                array_push($arrParams["account_uuids"], $objUser->accounts()->pluck("soundblock_accounts.account_uuid"));
            }
            unset($arrParams["user_uuids"]);
        }

        [$objDeployments, $availableMetaData] = $this->deploymentRepo->findAllWithRelationsAndStatus(
            ["project", "project.account", "project.artist", "platform", "collection"],
            $status,
            $per_page,
            $arrParams,
            $arrSort,
            $bnGroupByCollection
        );

        foreach ($objDeployments as $objDeployment) {
            $colLink = "office/soundblock/collection/" . $objDeployment->collection->collection_uuid . "/download";
            $objDeployment->collection->download_link = app_url("office") . $colLink;
            $objDeployment->project->load("format", "primaryGenre", "secondaryGenre", "language");
            unset(
                $objDeployment->project->format_uuid,
                $objDeployment->project->genre_primary_uuid,
                $objDeployment->project->genre_secondary_uuid,
                $objDeployment->project->project_language_uuid,
            );
        }

        return ([$objDeployments, $availableMetaData]);
    }

    public function findAllPending(){
        return ($this->deploymentRepo->findAllPending());
    }

    /**
     * @param string $project
     * @param int $perPage
     * @param array $option
     *
     * @return mixed
     * @throws \Exception
     */
    public function findAllByProject(string $project, int $perPage = 10, array $option = []) {
        /** @var Project */
        $objProject = $this->projectRepo->find($project, true);

        return ($this->deploymentRepo->findAllByProject($objProject, $option, $perPage));
    }

    /**
     * @param string $project
     * @param int $perPage
     * @return mixed
     * @throws \Exception
     */
    public function findProjectDeployments(string $project, int $perPage) {
        $objDeployments = $this->deploymentRepo->findProjectDeployments($project, $perPage);

        foreach ($objDeployments as $objDeployment) {
            $colLink = "office/soundblock/collection/" . $objDeployment->collection->collection_uuid . "/download";
            $objDeployment->collection->download_link = app_url("office") . $colLink;
        }

        return ($objDeployments);
    }

    /**
     * @param mixed $id
     * @param bool $bnFailure
     * @return DeploymentModel
     * @throws \Exception
     */
    public function find($id, ?bool $bnFailure = true): DeploymentModel {
        return ($this->deploymentRepo->find($id, $bnFailure));
    }

    /**
     * @param Project $project
     * @return DeploymentModel|null
     */
    public function findLatest(Project $project): ?DeploymentModel {
        return ($this->deploymentRepo->findLatest($project));
    }

    /**
     * @param string $deployment
     * @return array
     * @throws \Exception
     */
    public function getDeploymentInfo(string $deployment) {
        $objDeployment = $this->find($deployment);

        $deploymentInfo = [];
        $deploymentInfo["deployment"] = $objDeployment->toArray();
        $deploymentInfo["project"] = $objDeployment->project;

        if (is_object($deploymentInfo["project"])) {
            $deploymentInfo["project"]["account"] = $deploymentInfo["project"]->account;
            $deploymentInfo["project"]["artist"] = $deploymentInfo["project"]->artist;
        }

        $deploymentInfo["platform"] = $objDeployment->platform;
        $deploymentInfo["status"] = $objDeployment->status;
        $deploymentInfo["collection"] = $objDeployment->collection->toArray();
        $deploymentInfo["collection"]["files"] = [];

        if (!empty($objDeployment->metadata)) {
            $deploymentInfo["collection"]["files"] = $objDeployment->metadata["metadata_json"]["files"];
        } else {
            $arrMusics = $objDeployment->collection->tracks()->with("file")->get();

            foreach ($arrMusics as $objMusic) {
                $arrFile = $objMusic->file->toArray();
                $arrFile["track_number"] = $objMusic->track_number;
                $arrFile["track_isrc"] = $objMusic->track_isrc;
                $deploymentInfo["collection"]["files"][] = $arrFile;
            }
        }

        return ($deploymentInfo);
    }

    public function getCollectionDeployments(CollectionModel $objCollection) {
        $deploymentInfo = [];

        $deploymentInfo["collection"] = $objCollection->toArray();
        $deploymentInfo["deployments"] = $objCollection->deployments()->with("platform", "status")->get();
        $deploymentInfo["project"] = $objCollection->project()
            ->with("account", "artist", "artists", "format", "primaryGenre", "secondaryGenre", "language")
            ->first();
        unset(
            $deploymentInfo["project"]->format_uuid,
            $deploymentInfo["project"]->genre_primary_uuid,
            $deploymentInfo["project"]->genre_secondary_uuid,
            $deploymentInfo["project"]->project_language_uuid,
        );

        $arrMusics = $objCollection->tracks()->with("file")->get();

        foreach ($arrMusics as $objMusic) {
            $arrFile = $objMusic->file->toArray();
            $arrFile["track_number"] = $objMusic->track_number;
            $arrFile["track_isrc"] = $objMusic->track_isrc;
            $arrFile["primary_genre"] = $objMusic->primaryGenre->data_genre;
            $arrFile["secondary_genre"] = optional($objMusic->secondaryGenre)->data_genre;
            $arrFile["language_metadata"] = optional($objMusic->languageMetadata)->data_language;
            $arrFile["language_audio"] = optional($objMusic->languageAudio)->data_language;
            $arrFile["publishers"] = $objMusic->publisher;
            $arrFile["artists"] = $objMusic->artists;

            $objContributors = $objMusic->contributors;
            $arrContributors = [];
            if (!empty($objContributors)) {
                $objContributors = $objContributors->groupBy("contributor_uuid");
                foreach ($objContributors as $arrContributor) {
                    $arrContributorRolesNames = $this->contributorsRolesRepo->getNamesByUUids($arrContributor->pluck("contributor_role_uuid")->toArray());
                    $arrContributors[] = ["contributor" => $arrContributor->first()["contributor_name"], "types" => $arrContributorRolesNames->toArray()];
                }
            }

            $objArtists = $objMusic->artists;
            $arrArtists = [];
            if (!empty($objArtists)) {
                foreach ($objArtists as $key => $objArtist) {
                    $arrArtists[$key] = $objArtist->toArray();

                    if (!empty($arrArtists[$key]["url_apple"])) {
                        $arrParsedUrlApple = parse_url($arrArtists[$key]["url_apple"]);
                        $arrUrlApple = explode("/", $arrParsedUrlApple["path"]);
                        $arrArtists[$key]["url_apple"] = end($arrUrlApple);
                        $arrArtists[$key]["full_url_apple"] = $arrArtists[$key]["url_apple"];
                    }

                    if (!empty($arrArtists[$key]["url_spotify"])) {
                        $arrParsedUrlApple = parse_url($arrArtists[$key]["url_spotify"]);
                        $arrArtists[$key]["url_spotify"] = "spotify" . str_replace("/", ":", $arrParsedUrlApple["path"]);
                        $arrArtists[$key]["full_url_spotify"] = $arrArtists[$key]["url_spotify"];
                    }

                    if (!empty($arrArtists[$key]["url_soundcloud"])) {
                        $arrParsedUrlApple = parse_url($arrArtists[$key]["url_soundcloud"]);
                        $arrArtists[$key]["url_soundcloud"] = str_replace("/", "", $arrParsedUrlApple["path"]);
                        $arrArtists[$key]["full_url_soundcloud"] = $arrArtists[$key]["url_soundcloud"];
                    }
                }
            }

            $arrFile["contributors"] = $arrContributors;
            $arrFile["artists"] = $arrArtists;
            $arrFile["lyrics"] = $objMusic->lyrics;

            $deploymentInfo["collection"]["files"][] = $arrFile;
        }

        $newArtistArray = [];
        foreach ($deploymentInfo["project"]["artists"] as $key => $arrArtist) {
            $newArtistArray[$key] = $arrArtist->toArray();

            if (!empty($newArtistArray[$key]["url_apple"])) {
                $arrParsedUrlApple = parse_url($newArtistArray[$key]["url_apple"]);
                $arrUrlApple = explode("/", $arrParsedUrlApple["path"]);
                $newArtistArray[$key]["url_apple"] = end($arrUrlApple);
            }

            if (!empty($newArtistArray[$key]["url_spotify"])) {
                $arrParsedUrlApple = parse_url($newArtistArray[$key]["url_spotify"]);
                $newArtistArray[$key]["url_spotify"] = "spotify" . str_replace("/", ":", $arrParsedUrlApple["path"]);
            }

            if (!empty($newArtistArray[$key]["url_soundcloud"])) {
                $arrParsedUrlApple = parse_url($newArtistArray[$key]["url_soundcloud"]);
                $newArtistArray[$key]["url_soundcloud"] = str_replace("/", "", $arrParsedUrlApple["path"]);
            }
        }

        unset($deploymentInfo["project"]["artists"]);
        $deploymentInfo["project"]["artists"] = $newArtistArray;

        $arrTracksDownload = [];
        $arrTracksDownload["images"] = null;
        $objBucket = bucket_storage("office");

        $objDeploymentMetaFiles = $deploymentInfo["deployments"][0]->metadata->metadata_json["files"];

        foreach ($objCollection->tracks as $key => $objTrack) {
            $strTrackNumberPrefix = str_pad($objTrack->track_number, 3, 0, STR_PAD_LEFT);
            $strTrackVolumeNumberPrefix = str_pad($objTrack->track_volume_number, 2, 0, STR_PAD_LEFT);

            $fileKey = array_search($objTrack->file_uuid, array_column($objDeploymentMetaFiles, "file_uuid"));

            if ($fileKey !== false) {
                $strTrackS3Path = $objDeploymentMetaFiles[$fileKey]["s3_path"];
            } else {
                $strTrackS3Path = Soundblock::deployment_project_track_path($objTrack);
            }

            if ($objBucket->exists("public/" . $strTrackS3Path)) {
                $arrTracksDownload["tracks"][$strTrackVolumeNumberPrefix][$strTrackNumberPrefix] = str_replace(" ", "%20", cloud_url("office") . $strTrackS3Path);
            } else {
                $arrTracksDownload["tracks"][$strTrackVolumeNumberPrefix][$strTrackNumberPrefix] = null;
            }
        }

        if ($objBucket->exists("public/" . Soundblock::office_deployment_project_zip_path($objCollection))) {
            $arrTracksDownload["images"] = str_replace(" ", "%20", cloud_url("office") . Soundblock::office_deployment_project_zip_path($objCollection));
        }

        $deploymentInfo["download_links"] = $arrTracksDownload;
        $deploymentInfo["_path_test"] = Soundblock::office_deployment_project_zip_path($objCollection);

        $deploymentInfo["project"] = $deploymentInfo["project"]->toArray();
        $deploymentInfo["project"]["remote_addr"] = $objCollection->project->remote_addr;
        $deploymentInfo["project"]["fraud_score"] = $objCollection->project->fraud_score;

        return $deploymentInfo;
    }

    public function getPendingTakedowns(int $perPage){
        $objTakedowns = $this->deploymentTakedownRepo->getPendingTakedowns($perPage);

        $objTakedowns->getCollection()->transform(function ($objTakedown) {
            $objTakedown->account_name = $objTakedown->deployment->project->account->account_name;
            $objTakedown->artist_name = $objTakedown->deployment->project->project_artist;
            $objTakedown->project_title = $objTakedown->deployment->project->project_title;
            $objTakedown->project_release = $objTakedown->deployment->project->project_date;
            $objTakedown->created_by_name = $objTakedown->createdBy()->first()->name;
            $objTakedown->created_by_uuid = $objTakedown->createdBy()->first()->user_uuid;

            unset(
                $objTakedown["stamp_created"],
                $objTakedown["stamp_created_by"],
                $objTakedown["stamp_updated"],
                $objTakedown["stamp_updated_by"],
                $objTakedown["deployment"],
            );
            return $objTakedown;
        });

        return ($objTakedowns);
    }

    /**
     * @param array $arrParams
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public function createMultiple(array $arrParams) {
        $deployments = collect();

        foreach ($arrParams["deployments"] as $param) {
            $deployments->push($this->create($this->projectRepo->find($param["project"]), [$param["platform"]]));
        }

        return ($deployments);
    }

    /**
     * @param Project $objProject
     * @return mixed
     */
    public function setFlagPermanent(Project $objProject) {
        if(!empty($objProject->artists)) {
            foreach ($objProject->artists as $objArtist) {
                $this->artistService->setFlagPermanent($objArtist->artist_uuid, true);
            }
        }

        if(!empty($objProject->collections)) {
            $objCollection = $objProject->collections()->orderBy("stamp_created_at", "desc")->first();

            if(!empty($objCollection->tracks)){
                foreach ($objCollection->tracks as $objTrack) {
                    if(!empty($objTrack->contributors)){
                        foreach ($objTrack->contributors as $objContributor) {
                            $this->contributorRepo->setFlagPermanent($objContributor->contributor_uuid, true);
                        }
                    }
                    if(!empty($objTrack->artists)){
                        foreach ($objTrack->artists as $objArtist) {
                            $this->artistRepo->setFlagPermanent($objArtist->artist_uuid, true);
                        }
                    }
                }
            }
        }

        return (true);
    }

    /**
     * @param Project $project
     * @param array $arrPlatforms
     * @param string|null $collectionUuid
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public function create(Project $project, array $arrPlatforms, ?string $collectionUuid = null): \Illuminate\Support\Collection {
        $arrPlatformsModels = $this->platformRepo->findMany($arrPlatforms);
        $colDeployments = collect();
        $flagMusic = false;

        /** @var \App\Models\Soundblock\Collections\Collection $objCollection */
        if (is_null($collectionUuid)) {
            $objCollection = $this->colRepo->findLatestByProject($project);
        } else {
            $objCollection = $this->colRepo->find($collectionUuid);
        }

        if (is_null($objCollection)) {
            throw new \Exception("This Project Doesn't Have Any Collection.");
        }

        $objTracksVolumes = $objCollection->tracks->groupBy("track_volume_number");

        if ($objTracksVolumes->count() < $objCollection->project->project_volumes) {
            throw new \Exception("This project doesn't have tracks for each volume.");
        }

        foreach ($arrPlatformsModels as $objPlatform) {
            $arrDeployment = [];

            if ($objPlatform->flag_music == 1) {
                $flagMusic = true;
            }

            if (!$this->deploymentRepo->canDeployOnPlatform($objCollection, $objPlatform)) {
                throw new \Exception("Project {$project->project_uuid} is deployed on platform {$objPlatform->name}");
            }

            $arrDeployment["platform_id"] = $objPlatform->platform_id;
            $arrDeployment["platform_uuid"] = $objPlatform->platform_uuid;
            $arrDeployment["collection_id"] = $objCollection->collection_id;
            $arrDeployment["collection_uuid"] = $objCollection->collection_uuid;
            $arrDeployment["project_id"] = $project->project_id;
            $arrDeployment["project_uuid"] = $project->project_uuid;

            $objDeployment = $this->deploymentRepo->createModel($arrDeployment);

            $this->createDeploymentTracks($objDeployment, $objCollection);
            $this->createDeploymentStatus($objDeployment);

            $colDeployments->push($objDeployment->load(["platform", "status"]));

            dispatch(new DeploymentLedger(
                $objDeployment,
                DeploymentLedgerService::NEW_DEPLOYMENT,
                [
                    "remote_addr" => request()->getClientIp(),
                    "remote_host" => gethostbyaddr(request()->getClientIp()),
                    "remote_agent" => request()->server("HTTP_USER_AGENT")
                ]
            ))->onQueue("ledger");
            event(new DeploymentHistory($objDeployment, "create_deployment"));
        }

        if ($flagMusic) {
            $project->flag_music_permanent = true;
            $project->save();
        }

        return ($colDeployments);
    }

    /**
     * @param DeploymentModel $objDeployment
     * @param CollectionModel $objCollection
     * @return mixed
     * @throws \Exception
     */
    public function createDeploymentTracks(DeploymentModel $objDeployment, CollectionModel $objCollection){
        $arrData = [];

        if ($objDeployment->platform->flag_music) {
            $objMusicFiles = $objCollection->tracks;

            foreach ($objMusicFiles as $key => $item) {
                if ($item->track_number != $key+1) {
                    $item->track_number = $key+1;
                }

                $arrMeta = $item->file->meta;

                $arrMeta["preview_start"] = $this->prepareMetaTime($arrMeta["preview_start"] ?? "00:00");
                $arrMeta["preview_stop"] = $this->prepareMetaTime($arrMeta["preview_stop"] ?? "00:00");

                $arrData["files"][$key]["file_uuid"] = $item->file_uuid;
                $arrData["files"][$key]["file_name"] = $item->file->file_name;
                $arrData["files"][$key]["file_title"] = $item->file->file_title;
                $arrData["files"][$key]["file_size"] = $item->file->file_size;
                $arrData["files"][$key]["track_number"] = $item->track_number;
                $arrData["files"][$key]["meta"] = $arrMeta;
                $arrData["files"][$key]["s3_path"] = Soundblock::deployment_project_track_path($item);
            }
        }

        $objDeploymentMetadata = $objDeployment->metadata()->create([
            "row_uuid" => Util::uuid(),
            "deployment_uuid" => $objDeployment->deployment_uuid,
            "metadata_json" => $arrData
        ]);

        return ($objDeploymentMetadata);
    }

    /**
     * @param DeploymentModel $objDeployment
     * @return DeploymentStatus
     */
    public function createDeploymentStatus(DeploymentModel $objDeployment) {
        $arrStatus = $this->fillDeploymentStatusFields($objDeployment);

        return ($this->statusRepo->createModel($arrStatus));
    }

    private function fillDeploymentStatusFields(DeploymentModel $objDeployment) {
        $arrStatus = [];

        $arrStatus["deployment_id"] = $objDeployment->deployment_id;
        $arrStatus["deployment_uuid"] = $objDeployment->deployment_uuid;
        $arrStatus["deployment_status"] = $objDeployment->deployment_status;
        $arrStatus["deployment_memo"] = sprintf("The collection (%s) of project (%s) is deployed",
            $objDeployment->collection->collection_uuid, $objDeployment->project->project_uuid);

        return ($arrStatus);
    }

    public function createTakedown(DeploymentModel $objDeployment){
        $arrTakedown = [
            "takedown_uuid" => Util::uuid(),
            "deployment_id" => $objDeployment->deployment_id,
            "deployment_uuid" => $objDeployment->deployment_uuid
        ];
        $objTakedown = $this->deploymentTakedownRepo->create($arrTakedown);

        return ($objTakedown);
    }

    public function updateTakedown(string $uuidTakedown){
        $objTakedown = $this->deploymentTakedownRepo->find($uuidTakedown, true);
        $this->update($objTakedown->deployment, ["deployment_status" => "Removed"]);
        $boolResult = $objTakedown->update(["flag_status" => true]);

        return ($boolResult);
    }

    /**
     * @param DeploymentStatus $objStatus
     * @param DeploymentModel $objDeployment
     * @return mixed
     */
    public function updateDeploymentStatus(DeploymentStatus $objStatus, DeploymentModel $objDeployment) {
        $arrStatus = $this->fillDeploymentStatusFields($objDeployment);

        return ($this->statusRepo->update($objStatus, $arrStatus));
    }

    /**
     * @param DeploymentModel $objDeployment
     * @param array $arrParams
     * @return DeploymentModel
     * @throws \Exception
     */
    public function update(DeploymentModel $objDeployment, array $arrParams): DeploymentModel {
        $flagStatus = false;
        $arrDeployment = [];

        if (isset($arrParams["platform"])) {
            $objPlatform = $this->platformRepo->find($arrParams["platform"], true);
            $arrDeployment["platform_id"] = $objPlatform->platform_id;
            $arrDeployment["platform_uuid"] = $objPlatform->platform_uuid;
        }
        if (isset($arrParams["collection"])) {
            $objCol = $this->colRepo->find($arrParams["collection"], true);
            $objProject = $objCol->project;
            $arrDeployment["project_id"] = $objProject->project_id;
            $arrDeployment["project_uuid"] = $objProject->project_uuid;
            $arrDeployment["collection_id"] = $objCol->collection_id;
            $arrDeployment["collection_uuid"] = $objCol->collection_uuid;
        }
        if (isset($arrParams["deployment_status"])) {
            $flagStatus = true;
            $arrDeployment["deployment_status"] = $arrParams["deployment_status"];
        }
        $objDeployment = $this->deploymentRepo->update($objDeployment, $arrDeployment);

        dispatch(new DeploymentLedger(
            $objDeployment,
            $arrDeployment["deployment_status"] ?? 'Pending',
            [
                "remote_addr" => request()->getClientIp(),
                "remote_host" => gethostbyaddr(request()->getClientIp()),
                "remote_agent" => request()->server("HTTP_USER_AGENT")
            ],
            true
        ))->onQueue("ledger");
        event(new UpdateDeployment($objDeployment));

        if (isset($arrParams["deployment_status"]) && $arrParams["deployment_status"] == "Pending takedown") {
            event(new DeploymentHistory($objDeployment, "takedown"));
        } elseif (isset($arrParams["deployment_status"]) && $arrParams["deployment_status"] == "Redeploy") {
            event(new DeploymentHistory($objDeployment, "redeploy"));
        } else {
            event(new DeploymentHistory($objDeployment, "update_deployment"));
        }

        if ($flagStatus) {
            event(new DeploymentMail($objDeployment));
        }

        return ($objDeployment);
    }

    private function prepareMetaTime(string $strTime) {
        $arrParsed = explode(":", $strTime);
        $strSeconds = $arrParsed[0] % 60;
        $strMinutes = str_pad(($arrParsed[0] - $strSeconds) / 60, 2, "0", STR_PAD_LEFT);
        $strSeconds = str_pad($strSeconds, 2, "0", STR_PAD_LEFT);

        return  "$strMinutes:$strSeconds";
    }

    public function updateCollectionDeployments(CollectionModel $collection, array $arrParams) {
        if (isset($arrParams["deployment"]) || isset($arrParams["deployments"])) {
            $arrDeployments = isset($arrParams["deployment"]) ? [$arrParams["deployment"]] : $arrParams["deployments"];
            $objDeployments = $collection->deployments()->whereIn("deployment_uuid", $arrDeployments)->get();
        }

        if (empty($objDeployments)) {
            return (false);
        }

        foreach ($objDeployments as $objDeployment) {
            $this->update($objDeployment, ["deployment_status" => $arrParams["deployment_status"]]);
            $this->artistService->unsetFlagPermanentByDeployment($objDeployment);
        }

        $this->projectService->updateFlagMusicPermanent($collection->project);

        return $objDeployments;
    }
}
