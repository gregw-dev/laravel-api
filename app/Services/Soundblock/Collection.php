<?php

namespace App\Services\Soundblock;

use App\Contracts\{Core\Slack as SlackService, Core\Sox, Soundblock\Audit\Diskspace, Soundblock\Data\IsrcCodes};
use App\Events\Soundblock\OnHistory;
use App\Helpers\Filesystem\Soundblock;
use App\Jobs\{Soundblock\Ledger\CollectionLedger,
    Soundblock\Ledger\FileLedger,
    Soundblock\Ledger\TrackLedger,
    Soundblock\Projects\CopyFiles,
    Zip\ExtractProject,
    Zip\Zip};
use App\Models\{BaseModel,
    Common\QueueJob,
    Soundblock\Collections\Collection as SoundblockCollection,
    Soundblock\Files\File,
    Soundblock\Files\FileHistory,
    Soundblock\Projects\Project,
    Soundblock\Tracks\Track,
    Users\User};
use App\Repositories\{Common\Notification,
    Common\QueueJob as QueueJobRepository,
    Soundblock\ArtistPublisher as ArtistPublisherRepository,
    Soundblock\Collection as CollectionRepository,
    Soundblock\Contributor as ContributorRepository,
    Soundblock\Data\Contributors as ContributorsRoleRepository,
    Soundblock\Data\Genres as GenresRepository,
    Soundblock\Data\Languages as LanguagesRepository,
    Soundblock\Directory as DirectoryRepository,
    Soundblock\File as FileRepository,
    Soundblock\FileHistory as FileHistoryRepository,
    Soundblock\Project as ProjectRepository,
    Soundblock\Track as TrackRepository,
    Soundblock\TrackHistory as TrackHistoryRepository,
    User\User as UserRepository};
use App\Services\{Common\Zip as ZipService,
    Soundblock\Artist\Artist as ArtistService,
    Soundblock\File as FileService,
    Soundblock\Ledger\CollectionLedger as CollectionLedgerService,
    Soundblock\Ledger\TrackLedger as TrackLedgerService,
    Soundblock\Project as ProjectService};
use App\Traits\Soundblock\UpdateCollectionFiles;
use Auth;
use Client;
use Constant;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\{Collection as EloquentCollection};
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use Log;
use Symfony\Component\{HttpKernel\Exception\BadRequestHttpException};
use Util;
use wapmorgan\MediaFile\MediaFile;

class Collection
{
    use UpdateCollectionFiles;

    const ISRC_PREFIX = "US-AEA-81-";
    const USED_ISRC_INCREMENTS = [10000, 10001, 10002, 10003, 10004, 10005];
    protected CollectionRepository $colRepo;
    protected ProjectRepository $projectRepo;
    protected ZipService $zipService;
    protected FileRepository $fileRepo;
    protected FileHistoryRepository $fileHistoryRepo;
    protected DirectoryRepository $dirRepo;
    protected QueueJobRepository $queueJobRepo;
    protected Notification $notiRepo;
    private ProjectService $projectService;
    private FilesystemAdapter $soundblockAdapter;
    private UserRepository $userRepo;
    private \App\Services\Soundblock\File $fileService;
    /** @var Diskspace */
    private Diskspace $diskspaceAuditService;
    /** @var IsrcCodes */
    private IsrcCodes $isrcServices;
    /** @var \App\Contracts\Core\Sox */
    private Sox $soxService;
    /** @var ContributorsRoleRepository */
    private ContributorsRoleRepository $contributorsRoleRepo;
    /** @var ArtistService */
    private ArtistService $artistService;
    /** @var GenresRepository */
    private GenresRepository $genresRepo;
    /** @var LanguagesRepository */
    private LanguagesRepository $languagesRepo;
    /** @var ArtistPublisherRepository */
    private ArtistPublisherRepository $artistPublisherRepo;
    /** @var TrackHistoryRepository */
    private TrackHistoryRepository $trackHistoryRepo;
    /** @var TrackRepository */
    private TrackRepository $trackRepo;
    /**
     * @var ContributorRepository
     */
    private ContributorRepository $contributorRepo;
    private array $arrUserMeta;

    /**
     * Collection constructor.
     * @param CollectionRepository $colRepo
     * @param ProjectRepository $projectRepo
     * @param FileRepository $fileRepo
     * @param FileHistoryRepository $fileHistoryRepo
     * @param DirectoryRepository $dirRepo
     * @param ZipService $zipService
     * @param QueueJobRepository $queueJobRepo
     * @param Notification $notiRepo
     * @param \App\Services\Soundblock\Project $projectService
     * @param UserRepository $userRepo
     * @param \App\Services\Soundblock\File $fileService
     * @param Diskspace $diskspaceAuditService
     * @param IsrcCodes $isrcServices
     * @param Sox $soxService
     * @param ContributorsRoleRepository $contributorsRoleRepo
     * @param ArtistService $artistService
     * @param GenresRepository $genresRepo
     * @param LanguagesRepository $languagesRepo
     * @param ArtistPublisherRepository $artistPublisherRepo
     * @param TrackHistoryRepository $trackHistoryRepo
     * @param TrackRepository $trackRepo
     * @param ContributorRepository $contributorRepo
     */
    public function __construct(CollectionRepository $colRepo, ProjectRepository $projectRepo, FileRepository $fileRepo,
                                FileHistoryRepository $fileHistoryRepo, DirectoryRepository $dirRepo, ZipService $zipService,
                                QueueJobRepository $queueJobRepo, Notification $notiRepo, ProjectService $projectService,
                                UserRepository $userRepo, FileService $fileService, Diskspace $diskspaceAuditService,
                                IsrcCodes $isrcServices, Sox $soxService, ContributorsRoleRepository $contributorsRoleRepo,
                                ArtistService $artistService, GenresRepository $genresRepo, LanguagesRepository $languagesRepo,
                                ArtistPublisherRepository $artistPublisherRepo, TrackHistoryRepository $trackHistoryRepo,
                                TrackRepository $trackRepo, ContributorRepository $contributorRepo)
    {
        $this->colRepo = $colRepo;
        $this->dirRepo = $dirRepo;
        $this->userRepo = $userRepo;
        $this->notiRepo = $notiRepo;
        $this->fileRepo = $fileRepo;
        $this->trackRepo = $trackRepo;
        $this->zipService = $zipService;
        $this->soxService = $soxService;
        $this->genresRepo = $genresRepo;
        $this->projectRepo = $projectRepo;
        $this->fileService = $fileService;
        $this->queueJobRepo = $queueJobRepo;
        $this->isrcServices = $isrcServices;
        $this->artistService = $artistService;
        $this->languagesRepo = $languagesRepo;
        $this->projectService = $projectService;
        $this->fileHistoryRepo = $fileHistoryRepo;
        $this->contributorRepo = $contributorRepo;
        $this->contributorsRoleRepo = $contributorsRoleRepo;
        $this->trackHistoryRepo = $trackHistoryRepo;
        $this->artistPublisherRepo = $artistPublisherRepo;
        $this->diskspaceAuditService = $diskspaceAuditService;

        if (env("APP_ENV") == "local") {
            $this->soundblockAdapter = Storage::disk("local");
        } else {
            $this->soundblockAdapter = bucket_storage("soundblock");
        }

        $this->arrUserMeta = [
            "remote_addr" => request()->getClientIp(),
            "remote_host" => gethostbyaddr(request()->getClientIp()),
            "remote_agent" => request()->server("HTTP_USER_AGENT")
        ];
    }

    /**
     * @param array $a
     * @param array $b
     * @return int
     */
    public static function isVideo(array $a, array $b): int
    {
        if (isset($a["music"]) && isset($b["music"]))
            return (0);
        return (isset($a["music"]) ? -1 : 1);
    }

    /**
     * @param string $project
     * @param int $perPage
     * @param string $type
     * @param string|null $changedEntity
     * @return LengthAwarePaginator|EloquentCollection
     * @throws Exception
     */
    public function findAllByProject(string $project, int $perPage = 4, string $type = "soundblock", $changedEntity = null)
    {
        $objProject = $this->projectRepo->find($project, true);

        return ($this->colRepo->findAllByProject($objProject, $type, $perPage, $changedEntity));
    }

    /**
     * @param string $collection
     * @return array
     * @throws Exception
     */
    public function getTreeStructure(string $collection)
    {
        $objCol = $this->colRepo->find($collection);

        return ($this->colRepo->getTreeStructure($objCol));
    }

    /**
     * @param $id
     * @param bool $bnFaillure
     * @return mixed
     * @throws Exception
     */
    public function find($id, bool $bnFaillure = true)
    {
        return ($this->colRepo->find($id, $bnFaillure));
    }

    /**
     * @param SoundblockCollection $collection
     * @return EloquentCollection
     */
    public function getOrderedTracks($collection)
    {
        return ($this->colRepo->getOrderedTracks($collection));
    }

    /**
     * @param string $collection
     * @param string|null $path
     * @return array
     * @throws Exception
     */
    public function getResources(string $collection, ?string $path = null)
    {
        /** @var SoundblockCollection */
        $objCollection = $this->find($collection, true);
        $objResources = $this->colRepo->getResources($objCollection, $path)->toArray();
        $objResources["files"] = is_array($objResources["files"]) ? $objResources["files"] : $objResources["files"]->toArray();

        if (!empty($objResources["files"])) {
            foreach ($objResources["files"] as $key => $value) {
                $objUserCreate = $this->userRepo->find($value["stamp_created_by"]["uuid"]);
                $objUserUpdate = $this->userRepo->find($value["stamp_updated_by"]["uuid"]);

                $objResources["files"][$key]["stamp_created_by"]["avatar"] = $objUserCreate->avatar;
                $objResources["files"][$key]["stamp_updated_by"]["avatar"] = $objUserUpdate->avatar;
            }
        }

        if (!empty($objResources["directories"])) {
            foreach ($objResources["directories"] as $key => $value) {
                $objUserCreate = $this->userRepo->find($value["stamp_created_by"]["uuid"]);
                $objUserUpdate = $this->userRepo->find($value["stamp_updated_by"]["uuid"]);

                $objResources["directories"][$key]["stamp_created_by"]["avatar"] = $objUserCreate->avatar;
                $objResources["directories"][$key]["stamp_updated_by"]["avatar"] = $objUserUpdate->avatar;
            }
        }

        return ($objResources);
    }

    public function getCollectionTracks(string $collection)
    {
        $objCollection = $this->find($collection, true);
        $objCollection = $this->colRepo->getCollectionOrderedTracks($objCollection)->toArray();

        return ($objCollection);
    }

    /**
     * @param string $file
     * @return array
     * @throws Exception
     */
    public function getFilesHistory(string $file)
    {
        /** @var File */
        $objFile = $this->fileRepo->find($file, true);
        $latestHistory = $this->fileHistoryRepo->getLatestHistoryByFile($objFile);
        $arrNeedField = ["file_uuid", "file_action", FileHistory::STAMP_CREATED, FileHistory::STAMP_CREATED_BY, FileHistory::STAMP_UPDATED, FileHistory::STAMP_UPDATED_BY];
        $arrHistory = [];
        $arrLastCollections = [];
        array_push($arrHistory, $latestHistory->only($arrNeedField));

        while ($latestHistory->parent) {
            $arrLastCollections[] = $latestHistory->collection_id;
            /** @var FileHistory */
            $latestHistory = $latestHistory->parent()->whereNotIn("collection_id", $arrLastCollections)->latest()
                ->first();
            array_push($arrHistory, $latestHistory->only($arrNeedField));
        }

        return ($arrHistory);
    }

    /**
     * @param string $collection
     *
     * @return EloquentCollection
     * @throws Exception
     */
    public function getCollectionFilesHistory(string $collection)
    {
        return ($this->colRepo->getCollectionFilesHistory($collection));
    }

    /**
     * @param int $lastIsrc
     * @return string
     */
    public function generateIsrc(int $lastIsrc)
    {
        $isrc = self::ISRC_PREFIX . str_pad(++$lastIsrc, 5, 0, STR_PAD_LEFT);

        if (array_search($isrc, self::USED_ISRC_INCREMENTS) !== false) {
            return $this->generateIsrc(++$lastIsrc);
        }

        return ($isrc);
    }

    /**
     * @param array $fileUuids
     * @return mixed
     */
    public function getFileCategory(array $fileUuids)
    {
        return ($this->fileRepo->getFilesCategories($fileUuids));
    }

    /**
     * @param string $collection
     *
     * @return EloquentCollection
     * @throws Exception
     */
    public function getTracks($collection)
    {
        /** @var SoundblockCollection */
        $objCol = $this->find($collection);

        return ($this->fileRepo->getTracks($objCol));
    }

    /**
     * @param Project $objProject
     * @return null|SoundblockCollection
     */
    public function findLatestByProject(Project $objProject): ?SoundblockCollection
    {
        return ($this->colRepo->findLatestByProject($objProject));
    }

    /**
     * Minus match from $arrFiles
     * @param EloquentCollection|\Illuminate\Support\Collection $existFiles
     * @param array|EloquentCollection|\Illuminate\Support\Collection $arrFiles
     *
     * @return \Illuminate\Support\Collection
     */
    public function getFilesToAdd($existFiles, $arrFiles)
    {
        $existFiles = $existFiles->reject(function ($value) use ($arrFiles) {
            $flag = false;
            if (is_array($arrFiles)) {
                foreach ($arrFiles as $item) {
                    if (isset($item["file_uuid"])) {
                        if ($value->file_uuid === $item["file_uuid"])
                            $flag = true;
                    } else if (is_string($item)) {
                        if ($value->file_uuid === $item)
                            $flag = true;
                    } else {
                        throw new Exception("Invalid Parameter");
                    }
                }

            } else if ($arrFiles instanceof EloquentCollection || $arrFiles instanceof \Illuminate\Support\Collection) {
                foreach ($arrFiles as $item) {
                    if ($value->file_uuid === $item->file_uuid)
                        $flag = true;
                }
            } else {
                throw new Exception("Invalid Parameter");
            }
            return $flag;
        });

        return ($existFiles);
    }

    /**
     * Minus match from $arrDirs
     * @param EloquentCollection $arrExistDirs .
     * @param array/EloquentCollection $arrDirs
     * @return EloquentCollection
     * @throws Exception
     */
    public function getDirsToAdd(EloquentCollection $arrExistDirs, $arrDirs): EloquentCollection
    {
        if (!$arrExistDirs instanceof EloquentCollection)
            throw new Exception();

        $arrExistDirs = $arrExistDirs->reject(function ($value) use ($arrDirs) {
            $flag = false;
            if (is_array($arrDirs)) {
                foreach ($arrDirs as $item) {
                    if (isset($item["directory_uuid"])) {
                        if ($value->directory_uuid === $item["directory_uuid"])
                            $flag = true;
                    } else if (is_string($item)) {
                        if ($value->directory_uuid === $item)
                            $flag = true;
                    } else {
                        throw new Exception();
                    }
                }

            } else if ($arrDirs instanceof EloquentCollection) {
                foreach ($arrDirs as $item) {
                    if ($value->directory_uuid === $item->directory_uuid)
                        $flag = true;
                }
            } else {
                throw new Exception();
            }

            return $flag;
        });
        return $arrExistDirs;
    }

    /**
     * @param Project $objProject
     * @param string $strCollectionComment
     * @param array $arrFiles
     * @param array $arrUserMetaInfo
     * @return QueueJob
     * @throws Exception
     */
    public function processFilesJob(Project $objProject, string $strCollectionComment, array $arrFiles, array $arrUserMetaInfo): QueueJob
    {
        $queueJob = $this->createQueueJobAndSilentAlert();
        dispatch(new CopyFiles($queueJob, $objProject, $strCollectionComment, $arrFiles, $arrUserMetaInfo));

        return $queueJob;
    }
    
    public function checkCollectionTracksIsrcs(SoundblockCollection $objCollection){
        $boolResult = true;
        foreach ($objCollection->tracks as $objTrack) {
            if (empty($objTrack->track_isrc)) {
                $boolResult = false;
            }
        }

        return ($boolResult);
    }

    public function setTrackIsrc(Track $objTrack){
        if (empty($objTrack->track_isrc)) {
            $objIsrc = $this->isrcServices->getUnused();
            $objTrack->track_isrc = $objIsrc->data_isrc;
            $objTrack->save();
            $this->isrcServices->useIsrc($objIsrc);
        }

        return ($objTrack);
    }

    /**
     * @param array $arrParams
     * @return array
     * @throws Exception
     */
    public function uploadFile(array $arrParams): array
    {
        /** @var string $ext */
        $ext = $arrParams["file"]->getClientOriginalExtension();

        if (Util::lowerLabel($ext) == "zip") {
            $fileName = sprintf("%s.zip", Util::uuid());
            $uploadedFileName = $this->zipService->putFile($arrParams["file"], $fileName, Soundblock::upload_path());
        } else {
            $fileName = sprintf("%s.%s", Util::uuid(), $ext);
            $uploadedFileName = $this->zipService->putFile($arrParams["file"], $fileName, Soundblock::upload_path());
        }

        return ($uploadedFileName);
    }

    /**
     * Create a new Collection
     * @param Project $objProject
     * @param array $arrParams
     * @return SoundblockCollection
     * @property
     * collection_comment
     * project_uuid
     */
    public function create(Project $objProject, array $arrParams, ?User $objCreatedBy = null): SoundblockCollection
    {
        $arrCollection = [];

        $arrCollection["project_id"] = $objProject->project_id;
        $arrCollection["project_uuid"] = $objProject->project_uuid;
        $arrCollection["collection_comment"] = $arrParams["collection_comment"];
        $arrCollection["remote_addr"] = $arrParams["remote_addr"] ?? request()->getClientIp();
        $arrCollection["remote_host"] = $arrParams["remote_host"] ?? gethostbyaddr(request()->getClientIp());
        $arrCollection["remote_agent"] = $arrParams["remote_agent"] ?? request()->server("HTTP_USER_AGENT");

        if (is_object($objCreatedBy)) {
            $arrCollection[BaseModel::STAMP_CREATED_BY] = $objCreatedBy->user_id;
            $arrCollection[BaseModel::STAMP_UPDATED_BY] = $objCreatedBy->user_id;
        }

        return ($this->colRepo->create($arrCollection));
    }

    /**
     * @param SoundblockCollection $objCollection
     * @param array $arrFilesInfo
     * @param array $arrUserMetaInfo
     * @param User|null $objUser
     * @return \Illuminate\Support\Collection
     * @throws \Throwable
     */
    public function addFiles(SoundblockCollection $objCollection, array $arrFilesInfo, array $arrUserMetaInfo, ?User $objUser = null)
    {
        try {
            $arrVolumeTracksCount= [];
            $arrHistoryFiles = collect();

            foreach ($objCollection->tracks as $objTrack) {
                $arrVolumeTracksCount[$objTrack->track_volume_number][] = $objTrack->track_number;
            }

            \DB::beginTransaction();
            foreach ($arrFilesInfo as $key => $arrFileInfo){
                $arrHistory = [];
                $objProject = $objCollection->project;
                $strProjectPath = Soundblock::project_files_path($objProject);

                $arrFile = $this->getFileParameters($objCollection, $arrFileInfo, $strProjectPath, Soundblock::upload_path($arrFileInfo["file_name"]));

                if ($arrFile["file_category"] === Constant::MusicCategory) {
                    $objCollection->flag_changed_music = true;

                    if (isset($arrVolumeTracksCount[$arrFile["track_volume_number"]])){
                        $intTrackNum = count($arrVolumeTracksCount[$arrFile["track_volume_number"]]) + 1;
                        $arrFile["track_number"] = $intTrackNum;
                        $arrVolumeTracksCount[$arrFile["track_volume_number"]][] = $intTrackNum;
                    }else{
                        $arrVolumeTracksCount[$arrFile["track_volume_number"]][] = 1;
                        $arrFile["track_number"] = 1;
                    }

                    $objTracks = $objCollection->tracks;

                    foreach ($objTracks as $objTrack) {
                        if (($objTrack->track_number == $arrFile["track_number"]) && ($objTrack->track_volume_number == $arrFile["track_volume_number"])) {
                            throw new \Exception("Invalid track number.");
                        }
                    }

                } else if ($arrFile["file_category"] === Constant::VideoCategory) {
                    $objCollection->flag_changed_video = true;
                } else if ($arrFile["file_category"] === Constant::MerchCategory) {
                    $objCollection->flag_changed_merchandising = true;
                } else {
                    $objCollection->flag_changed_other = true;
                }

                $objCollection->save();

                if (!empty($arrFile["file_path"])) {
                    $objDirectory = $objCollection->directories()->where("directory_sortby", $arrFile["file_path"])
                        ->first();
                    $arrFile["directory_id"] = $objDirectory ? $objDirectory->directory_id : null;
                    $arrFile["directory_uuid"] = $objDirectory ? $objDirectory->directory_uuid : null;
                }

                $arrFile["remote_addr"] = $arrUserMetaInfo["remote_addr"] ?? "";
                $arrFile["remote_host"] = $arrUserMetaInfo["remote_host"] ?? "";
                $arrFile["remote_agent"] = $arrUserMetaInfo["remote_agent"] ?? "";

                $objFile = $this->fileRepo->createInCollection($arrFile, $objCollection, $objUser);

                $strRealPath = $strProjectPath . $objFile->file_uuid . "." . pathinfo($objFile->file_name, PATHINFO_EXTENSION);

                if (!$this->soundblockAdapter->exists($strRealPath)) {
                    throw new Exception("File wasn't moved to project path.");
                }

                $this->diskspaceAuditService->save($objProject, $arrFile["file_size"]);

                if ($objFile->file_category === Constant::MusicCategory) {
                    if ($arrFile["track_duration"] <= 30) {
                        $arrCodes = [0, $arrFile["track_duration"]];
                    } else if ($arrFile["track_duration"] >= 60) {
                        $arrCodes = [30, 60];
                    } else {
                        $arrCodes = [$arrFile["track_duration"] - 30, $arrFile["track_duration"]];
                    }

                    $this->fileService->addTimeCodes($objFile, ...$arrCodes);
                    $objTrack = $objFile->track;

                    $arrHistory["preview_start"] = $objTrack->preview_start;
                    $arrHistory["preview_stop"] = $objTrack->preview_stop;

                    foreach ($arrHistory as $column => $value) {
                        $this->trackHistoryRepo->create([
                            "track_id" => $objTrack->track_id,
                            "track_uuid" => $objTrack->track_uuid,
                            "field_name" => $column,
                            "old_value" => null,
                            "new_value" => $value,
                            Track::STAMP_CREATED_BY => $objTrack[Track::STAMP_CREATED_BY],
                            Track::STAMP_UPDATED_BY => $objTrack[Track::STAMP_UPDATED_BY]
                        ]);
                    }
                }

                dispatch(new FileLedger(
                    $objFile,
                    Soundblock::project_file_path($objCollection->project, $objFile),
                    $this->arrUserMeta
                ))->onQueue("ledger");

                if ($objFile->file_category == "music" && !is_null($objFile->track)) {
                    dispatch(new TrackLedger($objFile->track, TrackLedgerService::CREATE_EVENT, $this->arrUserMeta))->onQueue("ledger");
                }

                $arrHistoryFiles->push([
                    "new" => $objFile,
                ]);
            }

            \DB::commit();
        } catch (\Exception $exception) {
            \DB::rollBack();

            throw $exception;
        }

        return ($arrHistoryFiles);
    }

    public function createNewCollection(Project $objProject, string $strComment, $objUserMetaArray, ?User $objUser = null)
    {
        $objLatestCol = $this->colRepo->findLatestByProject($objProject);

        $arrCollection = [
            "project_id" => $objProject->project_id,
            "project_uuid" => $objProject->project_uuid,
            "collection_comment" => $strComment,
            "remote_addr" => $objUserMetaArray["remote_addr"] ?? "",
            "remote_host" => $objUserMetaArray["remote_host"] ?? "",
            "remote_agent" => $objUserMetaArray["remote_agent"] ?? ""
        ];

        $objNew = $this->create($objProject, $arrCollection, $objUser);


        if ($objLatestCol) {
            $objNew = $this->colRepo->attachResources($objNew, $objLatestCol, null, null, $objUser);
        }

        return $objNew;
    }

    /**
     * @param array $arrParams
     * @param array $arrUserMeta
     * @return QueueJob
     * @throws Exception
     */
    public function confirm(array $arrParams, array $arrUserMeta): QueueJob
    {
        $arrZip = $arrParams["file"];
        $arrFiles = $arrZip["zip_content"];

        usort($arrFiles, [Collection::class, "isVideo"]);
        $objProject = $this->projectRepo->find($arrParams["project"], true);
        $queueJob = $this->createQueueJobAndSilentAlert();
        $this->extractFiles($queueJob, $arrZip["file_name"], $arrFiles, $arrParams["collection_comment"], $objProject, $arrUserMeta);

        return ($queueJob);
    }

    /**
     * @param array $arrParams
     * @return SoundblockCollection
     * @throws FileNotFoundException
     */
    public function editFile(array $arrParams): SoundblockCollection
    {
        try {
            \DB::beginTransaction();

            $objProject = $this->projectRepo->find($arrParams["project"], true);
            $objCol = $this->colRepo->findLatestByProject($objProject);

            if (!$objCol) {
                throw new BadRequestHttpException("Project({$arrParams["project"]}) has n't any collection", null, 400);
            }

            $objNew = $this->create($objProject, $arrParams);
            $curArrFiles = $objCol->files;
            $arrFiles = $arrParams["files"];

            $objNew = $this->colRepo->attachResources($objNew, $objCol, null, $curArrFiles);
            $arrHistoryFiles = collect();
            $arrChanges = [];

            foreach ($arrFiles as $itemFile) {
                $objParentFile = $this->fileRepo->find($itemFile["file_uuid"], true);
                $objUpdated = clone $objParentFile;
                $arrChanges[$itemFile["file_uuid"]]["File title"] = [
                    "Previous value" => $objUpdated->file_title,
                    "Changed to" => $itemFile["file_title"]
                ];
                $objUpdated->file_title = $itemFile["file_title"];
                $objUpdated->save();

                if ($objParentFile->file_category == "music") {
                    [$objTrack, $arrChanges, $arrHistory] = $this->updateTrackFileData($objUpdated->track, $itemFile, $objNew, $arrChanges);
                }

                $arrHistoryFiles->push([
                    "parent" => $objParentFile,
                    "new" => $objUpdated,
                ]);
                $objUpdated->modified();
            }

            \DB::commit();
        } catch (\Exception $exception) {
            \DB::rollBack();

            throw $exception;
        }

        event(new OnHistory($objNew, "Modified", $arrHistoryFiles));

        foreach ($arrHistoryFiles as $arrHistory) {
            dispatch(new FileLedger(
                $arrHistory["parent"],
                Soundblock::project_file_path($objProject, $arrHistory["parent"]),
                $this->arrUserMeta
            ))->onQueue("ledger");
        }

        dispatch(new CollectionLedger(
            $objNew,
            CollectionLedgerService::CREATE_EVENT,
            $this->arrUserMeta,
            ["changes" => $arrChanges]
        ))->onQueue("ledger");

        return ($objNew);
    }

    public function updateFilePath(array $arrFileParams, SoundblockCollection $objCollection){
        $objFile = $this->fileRepo->find($arrFileParams["file_uuid"]);

        if (strtolower($objFile->file_category) !== "files") {
            throw new Exception("File must be under files category.");
        }

        $objDirectory = $objCollection->directories()->where("soundblock_files_directories.directory_uuid", $arrFileParams["directory_uuid"])->first();

        $objFile->update([
            "file_path" => $objDirectory->directory_sortby,
            "file_sortby" => str_replace("//", "/", $objDirectory->directory_sortby . "/" . $objFile->file_name)
        ]);

        return ($objFile);
    }

    public function updateTrackMeta($objTrack, array $arrTrackMeta, int $user_id)
    {
        $objProject = $this->projectRepo->find($arrTrackMeta["project"], true);
        $objCollection = $this->colRepo->findLatestByProject($objProject);

        [$objTrack, $arrChanges, $arrHistory] = $this->updateTrackFileData($objTrack, $arrTrackMeta, $objCollection, []);

        if (isset($arrTrackMeta["file_title"]) && ($objTrack->file->file_title != $arrTrackMeta["file_title"])) {
            $objFile = $objTrack->file;
            $prjPath = Soundblock::project_files_path($objProject);
            $strOldTitle = $objFile->file_title;
            $objFile->file_title = $arrTrackMeta["file_title"];
            $objFile->save();
            $objFile->refresh();

            $arrChanges["Track title"] = [
                "Previous value" => $strOldTitle,
                "Changed to" => $objFile->file_title
            ];
            $arrHistory["file_title"] = [
                "old" => $strOldTitle,
                "new" => $objFile->file_title
            ];

            dispatch(new FileLedger(
                $objFile,
                $prjPath . $objFile->file_uuid,
                $this->arrUserMeta
            ))->onQueue("ledger");
        }

        if (array_key_exists("artists", $arrTrackMeta)) {
            $oldArtists = $objTrack->artists;
            $objTrack->artists()->detach();

            if (!empty($arrTrackMeta["artists"])) {
                foreach ($arrTrackMeta["artists"] as $arrArtist) {
                    $objArtist = $this->artistService->findByUuid($arrArtist["artist"]);

                    if (!is_null($objArtist)) {
                        $objTrack->artists()->attach($objArtist->artist_id, [
                            "row_uuid" => Util::uuid(),
                            "file_id" => $objTrack->file_id,
                            "file_uuid" => $objTrack->file_uuid,
                            "track_uuid" => $objTrack->track_uuid,
                            "artist_uuid" => $objArtist->artist_uuid,
                            "artist_type" => $arrArtist["type"],
                            BaseModel::STAMP_CREATED => Util::current_time(),
                            BaseModel::STAMP_CREATED_BY => $user_id,
                            BaseModel::STAMP_UPDATED => Util::current_time(),
                            BaseModel::STAMP_UPDATED_BY => $user_id,
                        ]);
                    }
                }
            }

            $objTrack->refresh();

            if ($oldArtists->toArray() != $objTrack->artists->toArray()) {
                $arrChanges["Track artists"] = [
                    "Previous value" => json_encode($oldArtists),
                    "Changed to" => json_encode($objTrack->artists)
                ];
                $arrHistory["artists"] = [
                    "old" => json_encode($oldArtists),
                    "new" => json_encode($objTrack->artists)
                ];
            }
        }

        if (array_key_exists("contributors", $arrTrackMeta)) {
            $oldContributors = $objTrack->contributors;
            $objTrack->contributors()->detach();

            if (!empty($arrTrackMeta["contributors"])) {
                foreach ($arrTrackMeta["contributors"] as $arrayContributor) {
                    $objContributor = $this->contributorRepo->find($arrayContributor["contributor"]);

                    if ($objContributor) {
                        foreach ($arrayContributor["types"] as $type) {
                            $objContributorRole = $this->contributorsRoleRepo->find($type);

                            if ($objContributorRole) {
                                $objTrack->contributors()->attach($objContributor->contributor_id, [
                                    "row_uuid" => Util::uuid(),
                                    "contributor_id" => $objContributor->contributor_id,
                                    "contributor_uuid" => $objContributor->contributor_uuid,
                                    "contributor_role_id" => $objContributorRole->data_id,
                                    "contributor_role_uuid" => $objContributorRole->data_uuid,
                                    "track_id" => $objTrack->track_id,
                                    "track_uuid" => $objTrack->track_uuid,
                                    "file_id" => $objTrack->file_id,
                                    "file_uuid" => $objTrack->file_uuid,
                                    BaseModel::STAMP_CREATED => Util::current_time(),
                                    BaseModel::STAMP_CREATED_BY => $user_id,
                                    BaseModel::STAMP_UPDATED => Util::current_time(),
                                    BaseModel::STAMP_UPDATED_BY => $user_id,
                                ]);
                            }
                        }
                    }
                }
            }

            $objTrack->refresh();

            if ($oldContributors->toArray() != $objTrack->contributors->toArray()) {
                $arrChanges["Track contributors"] = [
                    "Previous value" => json_encode($oldContributors),
                    "Changed to" => json_encode($objTrack->contributors)
                ];
                $arrHistory["contributors"] = [
                    "old" => json_encode($oldContributors),
                    "new" => json_encode($objTrack->contributors)
                ];
            }
        }

        if (array_key_exists("publishers", $arrTrackMeta)) {
            $oldPublishers = $objTrack->publisher;
            $objTrack->publisher()->detach();

            if (!empty($arrTrackMeta["publishers"])) {
                foreach ($arrTrackMeta["publishers"] as $arrPublisher) {
                    $objArtistPublisher = $this->artistPublisherRepo->find($arrPublisher["publisher"]);

                    if (!is_null($objArtistPublisher)) {
                        $objTrack->publisher()->attach($objArtistPublisher->publisher_id, [
                            "row_uuid" => Util::uuid(),
                            "file_id" => $objTrack->file_id,
                            "file_uuid" => $objTrack->file_uuid,
                            "track_uuid" => $objTrack->track_uuid,
                            "publisher_uuid" => $objArtistPublisher->publisher_uuid,
                            BaseModel::STAMP_CREATED => Util::current_time(),
                            BaseModel::STAMP_CREATED_BY => $user_id,
                            BaseModel::STAMP_UPDATED => Util::current_time(),
                            BaseModel::STAMP_UPDATED_BY => $user_id,
                        ]);
                    }
                }
            }

            $objTrack->refresh();

            if ($oldPublishers->toArray() != $objTrack->publisher->toArray()) {
                $arrChanges["Track publishers"] = [
                    "Previous value" => json_encode($oldPublishers),
                    "Changed to" => json_encode($objTrack->publisher)
                ];
                $arrHistory["publishers"] = [
                    "old" => json_encode($oldPublishers),
                    "new" => json_encode($objTrack->publisher)
                ];
            }
        }

        if (!empty($arrHistory)) {
            foreach ($arrHistory as $field_name => $arrItem) {
                $this->trackHistoryRepo->create([
                    "track_id" => $objTrack->track_id,
                    "track_uuid" => $objTrack->track_uuid,
                    "field_name" => $field_name,
                    "old_value" => $arrItem["old"],
                    "new_value" => $arrItem["new"],
                ]);
            }

            dispatch(new TrackLedger($objTrack, TrackLedgerService::UPDATE_EVENT, $this->arrUserMeta, $arrChanges))->onQueue("ledger");
        }

        $objTrack = $objTrack->load(
            "languageMetadata",
            "languageAudio",
            "primaryGenre",
            "secondaryGenre",
            "artists",
            "contributors",
            "lyrics",
            "notes",
            "publisher"
        );

        $objContributors = $objTrack->contributors;

        if (!empty($objContributors)) {
            $arrContributors = [];

            $objContributors = $objContributors->groupBy("contributor_uuid");
            foreach ($objContributors as $arrContributor) {
                $arrContributors[] = ["contributor" => $arrContributor->first()["contributor_uuid"], "types" => $arrContributor->pluck("contributor_role_uuid")];
            }

            unset($objTrack->contributors);
            $objTrack->contributors = $arrContributors;
        }

        return ($objTrack);
    }

//    /**
//     * @param array $arrParams
//     * @return SoundblockCollection
//     * @throws Exception
//     */
//    public function organizeMusics(array $arrParams): SoundblockCollection {
//        $arrJobs = [];
//        $changes = [];
//
//        try {
//            \DB::beginTransaction();
//
//            $objCol = $this->find($arrParams["collection"]);
//            $organizeMusics = $arrParams["files"];
//            $objGroupedTracks = $objCol->tracks->groupBy("track_volume_number");
//
//            if (count($organizeMusics) != $objGroupedTracks[$arrParams["volume_number"]]->count()) {
//                throw new Exception("This track cannot be moved in that direction without first changing the volume number.", 400);
//            }
//
//            foreach ($objGroupedTracks[$arrParams["volume_number"]] as $objTrack) {
//                $key = array_search($objTrack->file_uuid, array_column($organizeMusics, "file_uuid"));
//
//                if ($key === false) {
//                    throw new Exception("Volume track can not be found in new order array.", 400);
//                }
//
//                $intNewNumber = $organizeMusics[$key]["track_number"];
//                $intOldNumber = $objTrack->track_number;
//
//                $objTrack->track_number = $intNewNumber;
//                $objTrack->save();
//
//                if ($intOldNumber !== $intNewNumber) {
//                    $this->trackHistoryRepo->create([
//                        "track_id" => $objTrack->track_id,
//                        "track_uuid" => $objTrack->track_uuid,
//                        "field_name" => "track_number",
//                        "old_value" => $intOldNumber,
//                        "new_value" => $intNewNumber,
//                    ]);
//                    $changes["Track Number"] = [
//                        "Previous value" => $intOldNumber,
//                        "Changed to" => $intNewNumber
//                    ];
//
//                    $arrJobs[] = new TrackLedger($objTrack, TrackLedgerService::UPDATE_EVENT, $changes);
//                }
//            }
//
//            \DB::commit();
//        } catch (\Exception $exception) {
//            \DB::rollBack();
//
//            throw $exception;
//        }
//
//        Bus::chain($arrJobs)->onQueue("ledger")->dispatch();
//
//        return ($objCol);
//    }

    public function swapTracks(array $arrParams): SoundblockCollection
    {
        /* Prepare General Data */
        $objCollection = $this->find($arrParams["collection"]);
        $objFirstTrack = $this->trackRepo->find($arrParams["track"]);
        $objSecondTrack = $this->trackRepo->find($arrParams["replace_track"]);

        /* Prepare Data for Replace */
        $arrReplaceData["first_track"]["track_number"] = $objFirstTrack->track_number;
        $arrReplaceData["first_track"]["track_volume_number"] = $objFirstTrack->track_volume_number;
        $arrReplaceData["second_track"]["track_number"] = $objSecondTrack->track_number;
        $arrReplaceData["second_track"]["track_volume_number"] = $objSecondTrack->track_volume_number;

        /* Update Firsts Track Data */
        $objFirstTrack->track_number = $arrReplaceData["second_track"]["track_number"];
        $objFirstTrack->track_volume_number = $arrReplaceData["second_track"]["track_volume_number"];
        $objFirstTrack->save();

        /* Update Seconds Track Data */
        $objSecondTrack->track_number = $arrReplaceData["first_track"]["track_number"];
        $objSecondTrack->track_volume_number = $arrReplaceData["first_track"]["track_volume_number"];
        $objSecondTrack->save();

        if ($arrReplaceData["first_track"]["track_number"] != $arrReplaceData["second_track"]["track_number"]) {
            $changesFirst = $this->trackHistoryRepo->createRecordWithBlockchain(
                $objFirstTrack,
                "track_number",
                $arrReplaceData["first_track"]["track_number"],
                $arrReplaceData["second_track"]["track_number"]
            );
            $changesSecond = $this->trackHistoryRepo->createRecordWithBlockchain(
                $objSecondTrack,
                "track_number",
                $arrReplaceData["second_track"]["track_number"],
                $arrReplaceData["first_track"]["track_number"]
            );
        }

        if ($arrReplaceData["first_track"]["track_volume_number"] != $arrReplaceData["second_track"]["track_volume_number"]) {
            $changesFirst = $this->trackHistoryRepo->createRecordWithBlockchain(
                $objFirstTrack,
                "track_volume_number",
                $arrReplaceData["first_track"]["track_volume_number"],
                $arrReplaceData["second_track"]["track_volume_number"]
            );
            $changesSecond = $this->trackHistoryRepo->createRecordWithBlockchain(
                $objSecondTrack,
                "track_volume_number",
                $arrReplaceData["second_track"]["track_volume_number"],
                $arrReplaceData["first_track"]["track_volume_number"]
            );
        }

        if (!empty($changesFirst) || !empty($changesSecond)) {
            dispatch(new TrackLedger($objFirstTrack, TrackLedgerService::UPDATE_EVENT, $this->arrUserMeta, $changesFirst))->onQueue("ledger");
            dispatch(new TrackLedger($objSecondTrack, TrackLedgerService::UPDATE_EVENT, $this->arrUserMeta, $changesSecond))->onQueue("ledger");
        }

        return ($objCollection);
    }

    public function reorderTracks(array $arrParams): SoundblockCollection
    {
        $objCollection = $this->find($arrParams["collection"]);
        $objOrderedTracks = $this->colRepo->getOrderedTracks($objCollection);
        $arrSortedTracks = array();
        $intLoopIndex = 1;
        $arrTrack = [];
        // make a sorted array but keep $arrParams["position"] index empty
        foreach ($objOrderedTracks as $objOrderedTrack) {
            if ($intLoopIndex == $arrParams["position"]) {
                $intLoopIndex++;
            }
            $arrTemp = [
                'track_uuid' => $objOrderedTrack->track_uuid,
                'track_volume_number' => $objOrderedTrack->track_volume_number,
                'track_number' => $objOrderedTrack->track_number
            ];

            if ($objOrderedTrack->track_uuid == $arrParams["track"]) {
                $arrTrack = $arrTemp;
                continue;
            }
            $arrSortedTracks[$intLoopIndex] = $arrTemp;
            $intLoopIndex++;
        }
        // Insert correct data  $arrParams["position"] index
        $arrSortedTracks[$arrParams["position"]] = $arrTrack;

        ksort($arrSortedTracks);

        // now make track volume and track number consecutive
        $arrChangedTracks = $this->makeVolumeAndTrackNumberConsecutive($arrSortedTracks, $arrParams['track']);

        foreach ($arrChangedTracks as $arrChangedTrack) {
            $this->updateTrackAndVolumeNumber($arrChangedTrack);
        }

        return ($objCollection);
    }

    private function makeVolumeAndTrackNumberConsecutive(array $arrSortedTracks, string $strTrackUUID)
    {
        $intVolumeNumber = 1;
        $intTrackNumber = 1;
        foreach ($arrSortedTracks as $intPosition => $arrTrack) {
            $boolVolChanged = false;
            $boolTrackNumberChanged = false;

            // volume number does not matches predicted volume number
            if ($intVolumeNumber != $arrTrack['track_volume_number']) {
                //If there is no previous track just assume the volume number to be a arbitrary large number
                $intPrevVolumeNumber = $arrSortedTracks[$intPosition - 1]['track_volume_number'] ?? 1;
                //If there is no next track just assume the volume number to be a arbitrary large number
                $intNextVolumeNumber = $arrSortedTracks[$intPosition + 1]['track_volume_number'] ?? 9999;

                // If this is first item of the collection Volume number must be 1
                if ($intPosition == 1) {
                    $intVolumeNumber = 1;
                    $boolVolChanged = true;
                } elseif ($intPrevVolumeNumber == $intNextVolumeNumber) { // if between another volume number
                    $intVolumeNumber = $intPrevVolumeNumber;
                    $boolVolChanged = true;
                } elseif ( ($arrTrack['track_volume_number'] - $intVolumeNumber) > 1) { // for example volume 3 after volume 1
                    $intVolumeNumber++;
                    $intTrackNumber = 1;
                    $boolVolChanged = true;
                } else {
                    $intVolumeNumber++;
                    $intTrackNumber = 1;
                    $boolVolChanged = false;
                }
            }

            if ($intTrackNumber != $arrTrack['track_number']) {
                $boolTrackNumberChanged = true;
            }

            if ($boolVolChanged) {
                $arrSortedTracks[$intPosition]['new_track_volume_number'] = $intVolumeNumber;
            }
            if ($boolTrackNumberChanged) {
                $arrSortedTracks[$intPosition]['new_track_number'] = $intTrackNumber;
            }
            $intTrackNumber++;
        }
        return ($arrSortedTracks);
    }

    private function updateTrackAndVolumeNumber($arrTrack)
    {
        if (!isset($arrTrack['new_track_volume_number']) && !isset($arrTrack['new_track_number'])) {
            return;
        }
        $objTrack = $this->trackRepo->find($arrTrack['track_uuid']);
        $arrChangesTrack=array();
        if (isset($arrTrack['new_track_volume_number'])) {
            $objTrack->track_volume_number = $arrTrack['new_track_volume_number'];

            $this->trackHistoryRepo->createRecordWithBlockchain(
                $objTrack,
                "track_volume_number",
                $arrTrack['track_volume_number'],
                $arrTrack['new_track_volume_number']
            );
            $arrChangesTrack["Track Volume Number"] = [
                "Previous value" => $arrTrack['track_volume_number'],
                "Changed to" => $arrTrack['new_track_volume_number']
            ];
        }

        if (isset($arrTrack['new_track_number'])) {
            $objTrack->track_number = $arrTrack['new_track_number'];

            $this->trackHistoryRepo->createRecordWithBlockchain(
                $objTrack,
                "track_number",
                $arrTrack['track_number'],
                $arrTrack['new_track_number']
            );
            $arrChangesTrack["Track Number"] = [
                "Previous value" => $arrTrack['track_number'],
                "Changed to" => $arrTrack['new_track_number']
            ];
        }
        $objTrack->save();

        if (!empty($arrChangesTrack)) {
            dispatch(new TrackLedger($objTrack, TrackLedgerService::UPDATE_EVENT, $this->arrUserMeta, $arrChangesTrack))->onQueue("ledger");
        }

    }

    public function getFilesDirectories(SoundblockCollection $objCollection){
        return ($objCollection->directoriesFiles);
    }

    /**
     * Add new directory
     * @param array $arrParams
     * @return SoundblockCollection
     * @throws Exception
     */
    public function addDirectory($arrParams): SoundblockCollection
    {
        $objProject = $this->projectRepo->find($arrParams["project"]);
        $objLatestCol = $this->colRepo->findLatestByProject($objProject);
        $objParentDir = $objLatestCol->directoriesFiles()->where("directory_sortby", $arrParams["directory_path"])->first();

        if (!empty($objParentDir)) {
            $arrParams["parent_directory"] = $objParentDir->directory_uuid;
        }

        $newCollection = $this->create($objProject, $arrParams);
        $this->dirRepo->createModel($arrParams, $newCollection);

        event(new OnHistory($newCollection, "Created", null, Auth::user(), $arrParams["directory_category"]));

        if (!$objLatestCol) {
            return ($newCollection);
        }

        $newCollection = $this->colRepo->attachResources($newCollection, $objLatestCol);

        dispatch(new CollectionLedger(
            $newCollection,
            CollectionLedgerService::CREATE_EVENT,
            $this->arrUserMeta
        ))->onQueue("ledger");

        return ($newCollection);
    }

    /**
     * Edit the name of directory and collection comment.
     * @param array $arrParams
     * @return SoundblockCollection
     * @throws Exception
     */
    public function editDirectory(array $arrParams)
    {
        $objProject = $this->projectRepo->find($arrParams["project"], true);
        $objCollection = $this->colRepo->findLatestByProject($objProject);

        if (!$objCollection) {
            throw new Exception("Project has n't any collection", 400);
        }

        $objDirectory = $objCollection->directoriesFiles()
            ->where("soundblock_files_directories.directory_uuid", $arrParams["directory"])
            ->first();

        if (empty($objDirectory)) {
            throw new Exception("Directory not exists.", 400);
        }

        $strOldDirName = $objDirectory->directory_name;
        $strOldDirSortBy = $objDirectory->directory_sortby;
        $strNewDirSortBy = $objDirectory->directory_path . "/" . $arrParams["directory_name"];

        if ($strOldDirName ==  $arrParams["directory_name"]) {
            throw new Exception("Same directory name.", 400);
        }

        $objDirectoriesUnderPath = $this->dirRepo->findAllUnderPath($objCollection, $objDirectory->directory_sortby);
        $objFilesInDirectory = $this->dirRepo->getFilesInDir($objDirectory, $objCollection);

        $objDirectory->update([
            "directory_name" => $arrParams["directory_name"],
            "directory_sortby" => $strNewDirSortBy,
        ]);

        if (!empty($objDirectoriesUnderPath)) {
            foreach ($objDirectoriesUnderPath as $objDirUnderPath) {
                $strNewPath = str_replace($strOldDirSortBy, $strNewDirSortBy, $objDirUnderPath->directory_path);
                $strNewSortBy = str_replace($strOldDirSortBy, $strNewDirSortBy, $objDirUnderPath->directory_sortby);

                $objDirUnderPath->directory_path = $strNewPath;
                $objDirUnderPath->directory_sortby = $strNewSortBy;
                $objDirUnderPath->save();
            }
        }

        if (!empty($objFilesInDirectory)) {
            foreach ($objFilesInDirectory as $objFileInDir) {
                $strNewFilePath = str_replace($strOldDirSortBy, $strNewDirSortBy, $objFileInDir->file_path);
                $strNewFileSortBy = str_replace($strOldDirSortBy, $strNewDirSortBy, $objFileInDir->file_sortby);

                $objFileInDir->file_path = $strNewFilePath;
                $objFileInDir->file_sortby = $strNewFileSortBy;
                $objFileInDir->save();

                dispatch(new FileLedger(
                    $objFileInDir,
                    Soundblock::project_file_path($objProject, $objFileInDir),
                    $this->arrUserMeta
                ))->onQueue("ledger");
            }
        }

        $objCollection->update(["flag_changed_other" => true]);

        event(new OnHistory($objCollection, "modified", null, null, "Files"));
        dispatch(new CollectionLedger(
            $objCollection,
            CollectionLedgerService::UPDATE_EVENT,
            $this->arrUserMeta
        ))->onQueue("ledger");

        return ($objCollection);
    }

    /**
     * Restore old version file
     * @param array $arrParams
     * @return SoundblockCollection|CollectionRepository
     * @throws Exception
     */
    public function restore(array $arrParams)
    {
        try {
            \DB::beginTransaction();

            $arrRestoreFiles = $this->fileRepo->findWhere($arrParams["files"]);

            if ($arrRestoreFiles->count() === 0) {
                abort(400, "No files to restore.");
            }

            /** @var SoundblockCollection $collection */
            $collection = $this->find($arrParams["collection"]);
            /** @var Project $objProject */
            $objProject = $collection->project;
            $objLatestCol = $this->colRepo->findLatestByProject($objProject);
            $objNewCol = $this->create($objProject, $arrParams);
            $arrHistoryFiles = collect();

            foreach ($arrRestoreFiles as $objFile) {
                $arrHistoryFiles->push([
                    "parent" => $objFile,
                    "new" => $objFile,
                ]);
            }
            $arrFilesToAttach = $this->getFilesToAdd($objLatestCol->files, $arrRestoreFiles);
            $objNewCol = $this->colRepo->attachResources($objNewCol, $objLatestCol, null, $arrFilesToAttach);
            $trackFiles = $arrRestoreFiles->where("file_category", "music");

            if (!empty($trackFiles)) {
                $intCollectionFilesCount = $objNewCol->tracks()->count();

                foreach ($trackFiles as $index => $objFile) {
                    $objFile->track->track_number = $intCollectionFilesCount + $index + 1;
                }
            }

            $objNewCol = $this->colRepo->attachFiles($objNewCol, $arrRestoreFiles);


            \DB::commit();
        } catch (\Exception $exception) {
            \DB::rollBack();

            throw $exception;
        }

        event(new OnHistory($objNewCol, "Restored", $arrHistoryFiles));

        foreach ($arrRestoreFiles as $objFile) {
            dispatch(new FileLedger(
                $objFile,
                Soundblock::project_file_path($objProject, $objFile),
                $this->arrUserMeta
            ))->onQueue("ledger");
        }

        dispatch(new CollectionLedger(
            $objNewCol,
            CollectionLedgerService::CREATE_EVENT,
            $this->arrUserMeta
        ))->onQueue("ledger");

        return ($objNewCol);
    }

    /**
     * Revert the file
     * @param array $arrParams
     * @return SoundblockCollection|CollectionRepository
     * @throws Exception
     */
    public function revert(array $arrParams)
    {
        try {
            \DB::beginTransaction();

            $arrRevertFiles = $this->fileRepo->findWhere($arrParams["files"]);
            /** @var SoundblockCollection */
            $collection = $this->find($arrParams["collection"], true);
            if ($arrRevertFiles->count() == 0)
                throw new Exception("No files to revert.");

            $arrChildFiles = collect();

            foreach ($arrRevertFiles as $revertFile) {
                $childFile = $this->fileHistoryRepo->findChild($revertFile);

                if (is_object($childFile)) {
                    $arrChildFiles->push($childFile);
                }
            }
            $objProject = $collection->project;
            $objLatestCol = $this->findLatestByProject($objProject);
            $arrHistoryFiles = collect();

            foreach ($arrRevertFiles as $objFile) {
                $arrHistoryFiles->push([
                    "new" => $objFile,
                    "parent" => $objFile,
                ]);
            }
            $arrFilesToAttach = $this->getFilesToAdd($objLatestCol->files, $arrChildFiles);
            $objNewCol = $this->create($objProject, $arrParams);
            $objNewCol = $this->colRepo->attachResources($objNewCol, $objLatestCol, null, $arrFilesToAttach);
            $objNewCol = $this->colRepo->attachFiles($objNewCol, $arrRevertFiles);

            \DB::commit();
        } catch (\Exception $exception) {
            \DB::rollBack();

            throw $exception;
        }

        event(new OnHistory($objNewCol, "Reverted", $arrHistoryFiles));

        dispatch(new CollectionLedger(
            $objNewCol,
            CollectionLedgerService::CREATE_EVENT,
            $this->arrUserMeta
        ))->onQueue("ledger");

        return ($objNewCol);
    }

    /**
     * @param array $arrParams
     * @return SoundblockCollection
     * @throws Exception
     */
    public function deleteFiles(array $arrParams): SoundblockCollection
    {
        try {
            \DB::beginTransaction();

            $objProject = $this->projectRepo->find($arrParams["project"], true);
            $objCol = $this->findLatestByProject($objProject);

            if (!$objCol) {
                throw new BadRequestHttpException("Project({$arrParams["project"]}) has n't any collection", null, 400);
            }

            if (!$this->colRepo->hasFiles($objCol, collect($arrParams["files"])->pluck("file_uuid")->toArray())) {
                throw new Exception("Collection {$objCol->collection_uuid} has n't these files.");
            }

            $arrFilesToDel = $arrParams["files"];
            $arrToAddFiles = $this->getFilesToAdd($objCol->files, $arrFilesToDel);
            $objNew = $this->create($objCol->project, $arrParams);
            $objNew = $this->colRepo->attachResources($objNew, $objCol, null, $arrToAddFiles);
            $arrParentFiles = $this->fileRepo->findWhere($arrFilesToDel);
            $arrHistoryFiles = collect();

            foreach ($arrParentFiles as $objFile) {
                $arrHistoryFiles->push([
                    "new" => $objFile,
                    "parent" => $objFile,
                ]);
            }

            $orderedFilesGrouped = $objNew->tracks->groupBy("track_volume_number");

            foreach ($orderedFilesGrouped as $objTracks) {
                foreach ($objTracks as $key => $objTrack) {
                    $objTrack->track_number = $key + 1;
                    $objTrack->save();
                }
            }

            \DB::commit();
        } catch (\Exception $exception) {
            \DB::rollBack();

            throw $exception;
        }

        event(new OnHistory($objNew, "Deleted", $arrHistoryFiles));

        foreach ($arrParentFiles as $objFile) {
            dispatch(new FileLedger(
                $objFile,
                Soundblock::project_file_path($objProject, $objFile),
                $this->arrUserMeta
            ))->onQueue("ledger");
        }

        dispatch(new CollectionLedger(
            $objNew,
            CollectionLedgerService::CREATE_EVENT,
            $this->arrUserMeta,
            ["deleted" => $arrParentFiles]
        ))->onQueue("ledger");

        return ($objNew);
    }

    /**
     * @param string $strCollection
     * @param array $arrParam
     * @param User $objUser
     * @return QueueJob
     * @throws Exception
     */
    public function zipFiles(string $strCollection, array $arrParam, User $objUser): QueueJob
    {
        /** @var SoundblockCollection */
        $collection = $this->find($strCollection, true);

        if (!$this->colRepo->hasFiles($collection, collect($arrParam["files"])->pluck("file_uuid")->toArray())) {
            throw new BadRequestHttpException("Collection ({$collection->collection_uuid}) has not these files", null, 400);
        }

        $queueJob = $this->createQueueJobAndAlertForZip();
        $files = $this->fileRepo->findWhere($arrParam["files"]);

        dispatch(new Zip($queueJob, $collection, $objUser, $files));

        return ($queueJob);
    }

    /**
     * @param array $arrParams
     * @return SoundblockCollection
     * @throws Exception
     */
    public function deleteDirectory(array $arrParams): SoundblockCollection
    {
        try {
            \DB::beginTransaction();
            $objProject = $this->projectRepo->find($arrParams["project"], true);
            $objCol = $this->findLatestByProject($objProject);
            $objDir = $this->dirRepo->find($arrParams["directory"]);
            $arrFilesInDir = $this->dirRepo->getFilesInDir($objDir, $objCol);

            $objNew = $this->create($objCol->project, $arrParams);
            $arrExistFiles = $objCol->files;
            $arrToAddFiles = $this->getFilesToAdd($arrExistFiles, $arrFilesInDir);

            $objDirectoriesUnderPath = $this->dirRepo->findAllUnderPath($objCol, $objDir->directory_sortby);

            if (!empty($objDirectoriesUnderPath)) {
                foreach ($objDirectoriesUnderPath as $objDirUnderPath) {
                    $arrDirsToRmv[] = ["directory_uuid" => $objDirUnderPath->directory_uuid];
                }
            }

            $arrDirsToRmv[] = [
                "directory_uuid" => $objDir->directory_uuid,
            ];

            $arrDirsToRmv = array_map("unserialize", array_unique(array_map("serialize", $arrDirsToRmv)));

            $arrExistDirs = $objCol->directories;
            $arrDirsToAdd = $this->getDirsToAdd($arrExistDirs, $arrDirsToRmv);

            $objNew = $this->colRepo->attachResources($objNew, $objCol, $arrDirsToAdd, $arrToAddFiles);

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();

            throw $e;
        }

        $arrHistoryFiles = collect();

        foreach ($arrFilesInDir as $objFile) {
            $arrHistoryFiles->push([
                "parent" => $objFile,
                "new" => $objFile,
            ]);
        }

        if ($arrHistoryFiles->count() == 0) {
            event(new OnHistory($objNew, "Deleted", $arrHistoryFiles));
        } else {
            event(new OnHistory($objNew, "Deleted", $arrHistoryFiles, null, $arrParams["file_category"]));
        }

        dispatch(new CollectionLedger(
            $objNew,
            CollectionLedgerService::CREATE_EVENT,
            $this->arrUserMeta
        ))->onQueue("ledger");

        return ($objNew);
    }

    /**
     * @return QueueJob
     * @throws Exception
     */
    protected function createQueueJobAndAlertForZip(): QueueJob
    {
        $app = Client::app();
        /** @var User $objUser */
        $objUser = Auth::user();

        $queueJobParams = [
            "user_id" => $objUser->user_id,
            "user_uuid" => $objUser->user_uuid,
            "app_id" => $app->app_id,
            "app_uuid" => $app->app_uuid,
        ];
        switch ($app->app_name) {
            case "soundblock" :
            {
                $queueJobParams = array_merge($queueJobParams, ["job_type" => "Job.Soundblock.Project.Download"]);
                break;
            }
            case "office" :
            {
                $queueJobParams = array_merge($queueJobParams, ["job_type" => "Job.Office.Project.Download"]);
                break;
            }
            default:
                break;
        }
        $queueJob = $this->queueJobRepo->createModel($queueJobParams);
        $notificationParams = [
            "app_id" => $app->app_id,
            "app_uuid" => $app->app_uuid,
            "notification_name" => "Silent Alert",
            "notification_memo" => "This notification is silent alert",
            "notification_action" => "Not Triggered",
        ];
        $userNotificationParams = [
            "notification_state" => "read",
            "flag_canarchive" => true,
            "flag_candelete" => true,
            "flag_email" => false,
        ];
        $notification = $this->notiRepo->createModel($notificationParams);
        $this->notiRepo->attachUser($notification, $objUser, $userNotificationParams);

        return ($queueJob);
    }

    /**
     * @return QueueJob
     * @throws Exception
     */
    protected function createQueueJobAndSilentAlert(): QueueJob
    {
        $app = Client::app();
        $queueJob = $this->queueJobRepo->createModel([
            "user_id" => Auth::id(),
            "user_uuid" => Auth::user()->user_uuid,
            "app_id" => $app->app_id,
            "app_uuid" => $app->app_uuid,
            "job_type" => "Job.Soundblock.Project.Collection.Extract",
            "flag_status" => "Pending",
            "flag_silentalert" => 1,
        ]);

        $arrNotiParams = [
            "app_id" => $app->app_id,
            "app_uuid" => $app->app_uuid,
            "notification_name" => "Silent Alert",
            "notification_memo" => "This notification is silent alert",
            "notification_action" => "Not Triggered",
        ];
        $arrUserNotiParams = [
            "notification_state" => "read",
            "flag_canarchive" => true,
            "flag_candelete" => true,
            "flag_email" => false,
        ];
        $notification = $this->notiRepo->createModel($arrNotiParams);
        $this->notiRepo->attachUser($notification, Auth::user(), $arrUserNotiParams);

        return ($queueJob);
    }

    /**
     * @param QueueJob $queueJob
     * @param string $uploadedFileName
     * @param array $files
     * @param string $strComment
     * @param Project $project
     * @param array $arrUserMeta
     */
    protected function extractFiles(QueueJob $queueJob, string $uploadedFileName, array $files, string $strComment, Project $project, array $arrUserMeta): void
    {
        dispatch(new ExtractProject($queueJob, $uploadedFileName, $files, $strComment, $project, $arrUserMeta));
    }

    /**
     * Create an array parameters
     * @param SoundblockCollection $collection
     * @param array $arrFile
     * @param string $dest
     * @param string $savePath
     * @return array
     * @throws Exception
     */
    private function getFileParameters(SoundblockCollection $collection, array $arrFile, string $dest, string $savePath): array
    {
        if (!$this->soundblockAdapter->exists($savePath)) {
            throw new Exception("File not uploaded.", 400);
        }

        $ext = pathinfo($arrFile["file_name"], PATHINFO_EXTENSION);
        $physicalName = Util::uuid();
        $strProjectFilePath = $dest . $physicalName . "." . $ext;

        if ($this->soundblockAdapter->getDriver()->getAdapter() instanceof AwsS3Adapter) {
            Storage::disk("local")->writeStream($strProjectFilePath, $this->soundblockAdapter->readStream($savePath));
            $strPath = Storage::disk("local")->path($strProjectFilePath);
        } else {
            $strPath = $this->soundblockAdapter->path($savePath);
        }

        $boolResult = $this->soundblockAdapter->move($savePath, $strProjectFilePath);

        if (!$boolResult) {
            if (Storage::disk("local")->exists($strProjectFilePath)) {
                Storage::disk("local")->delete($strProjectFilePath);
            }
            $objSlackService = resolve(SlackService::class);
            $objSlackService->reportMoveFileFails($collection->project);
            throw new Exception("File wasn't moved to project path.");
        }

        $md5File = md5_file($strPath);

        $size = $this->soundblockAdapter->size($dest . $physicalName . "." . $ext);

        $arrFile = array_merge($arrFile, [
            "file_uuid" => $physicalName,
            "file_category" => $arrFile["file_category"],
            "file_size" => $size,
            "file_md5" => $md5File,
            "file_ext" => $ext,
        ]);

        if ($arrFile["file_category"] == "music") {
            try {
                $media = MediaFile::open($strPath);
                if ($media->isAudio()) {
                    $audio = $media->getAudio();
                    $duration = intval($audio->getLength());
                }
            } catch (\Exception $exception) {
                info($exception->getMessage());
//                throw $exception;
            }

            $arrFile["track_duration"] = isset($duration) ? floor($duration) : 0;

            try {
                $strProcessedPath = $this->soxService->convert($strPath);

                if ($strProcessedPath !== $strPath) {
                    $this->soundblockAdapter->delete($strProjectFilePath);
                    $this->soundblockAdapter->writeStream($strProjectFilePath, Storage::disk("local")
                        ->readStream($dest . $physicalName . "_processed.$ext"));
                    Storage::disk("local")->delete($dest . $physicalName . "_processed.$ext");
                }
            } catch (\Exception $exception) {
                info($exception);
            }
        }

        Storage::disk("local")->delete($strProjectFilePath);

        if (!isset($arrFile["file_path"])) {
            $arrFile["file_path"] = Util::ucfLabel($arrFile["file_category"]);
        } else {
            $arrPath = explode("/", $arrFile["file_path"]);
            $arrPath[0] = strtolower($arrPath[0]) === strtolower($arrFile["file_category"]) ? $arrPath[0] : ucfirst($arrFile["file_category"]);
            $arrFile["file_path"] = implode("/", $arrPath);
        }

        $arrFile["file_sortby"] = $arrFile["file_path"] . DIRECTORY_SEPARATOR . $arrFile["file_name"];

        if (isset($arrFile["track"]["file_uuid"]) && $arrFile["file_category"] == "video") {
            $objFileMusic = $this->fileRepo->find($arrFile["track"]["file_uuid"], true);
            $arrFile["music_id"] = $objFileMusic->file_id;
            $arrFile["music_uuid"] = $objFileMusic->file_uuid;
        }

        $arrFile = Util::rename_file($collection, $arrFile);

        return ($arrFile);
    }
}
