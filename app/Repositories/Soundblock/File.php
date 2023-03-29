<?php

namespace App\Repositories\Soundblock;

use Util;
use Auth;
use App\Contracts\Soundblock\Artist\Artist;
use Illuminate\Support\Collection as SupportCollection;
use App\Contracts\Soundblock\Data\IsrcCodes as IsrcCodesContract;
use App\Models\{
    Soundblock\Files\File as FileModel,
    Soundblock\Collections\Collection,
    Soundblock\Files\FileMerch,
    Soundblock\Tracks\Track,
    Soundblock\Files\FileOther,
    Soundblock\Files\FileVideo,
    BaseModel,
    Users\User
};
use App\Repositories\{
    BaseRepository,
    Soundblock\Data\Genres as GenresRepository,
    Soundblock\Data\Languages as LanguagesRepository,
    Soundblock\Data\Contributors as ContributorsRoleRepository,
    Soundblock\ArtistPublisher as ArtistPublisherRepository,
    Soundblock\TrackHistory as TrackHistoryRepository,
    Soundblock\Contributor as ContributorRepository
};

class File extends BaseRepository {

    protected Track $trackModel;
    /** @var LanguagesRepository */
    private LanguagesRepository $languagesRepo;
    /** @var Artist */
    private Artist $artistService;
    /** @var ContributorsRoleRepository */
    private ContributorsRoleRepository $contributorsRoleRepo;
    /** @var ArtistPublisher */
    private ArtistPublisher $artistPublisherRepo;
    /** @var GenresRepository */
    private GenresRepository $genresRepo;
    /** @var TrackHistory */
    private TrackHistory $trackHistoryRepo;
    /**
     * @var Contributor
     */
    private Contributor $contributorRepo;
    /** @var IsrcCodesContract */
    private IsrcCodesContract $isrcService;

    /**
     * @param FileModel $file
     * @param Track $objTrack
     * @param LanguagesRepository $languagesRepo
     * @param Artist $artistService
     * @param ContributorsRoleRepository $contributorsRoleRepo
     * @param ArtistPublisher $artistPublisherRepo
     * @param GenresRepository $genresRepo
     * @param TrackHistory $trackHistoryRepo
     * @param Contributor $contributorRepo
     * @param IsrcCodesContract $isrcService
     */
    public function __construct(FileModel $file, Track $objTrack, LanguagesRepository $languagesRepo,
                                Artist $artistService, ContributorsRoleRepository $contributorsRoleRepo,
                                ArtistPublisherRepository $artistPublisherRepo, GenresRepository $genresRepo,
                                TrackHistoryRepository $trackHistoryRepo, ContributorRepository $contributorRepo,
                                IsrcCodesContract $isrcService) {
        $this->model = $file;
        $this->trackModel = $objTrack;
        $this->languagesRepo = $languagesRepo;
        $this->artistService = $artistService;
        $this->contributorsRoleRepo = $contributorsRoleRepo;
        $this->artistPublisherRepo = $artistPublisherRepo;
        $this->genresRepo = $genresRepo;
        $this->trackHistoryRepo = $trackHistoryRepo;
        $this->contributorRepo = $contributorRepo;
        $this->isrcService = $isrcService;
    }

    /**
     * @param array $arrWhere
     * @param string $fields
     * @param string|null $orderBy
     * @return SupportCollection
     * @throws \Exception
     */
    public function findWhere(array $arrWhere, string $fields = "uuid", string $orderBy = null): SupportCollection {
        if ($fields === "uuid") {
            $files = collect($arrWhere)->pluck("file_uuid");
            if (empty($files))
                $files = $arrWhere;
            if ($orderBy) {
                return ($this->model->whereIn("file_uuid", $files)->orderBy($orderBy, "asc")->get());
            } else {
                return ($this->model->whereIn("file_uuid", $files)->get());
            }

        } else if ($fields === "id") {
            if ($orderBy) {
                return ($this->model->whereIn("file_id", $arrWhere)->orderBy($orderBy, "asc")->get());
            } else {
                return ($this->model->whereIn("file_id", $arrWhere)->get());
            }
        } else {
            throw new \Exception();
        }
    }

    /**
     * @param $id
     * @return mixed
     */
    public function findById($id) {
        if (is_int($id)) {
            return ($this->model->findOrFail($id));
        } else if (is_string($id)) {
            return ($this->model->where("file_uuid", $id)->firstOrFail());
        }
    }

    /**
     * @param User $objUser
     * @return SupportCollection
     */
    public function findNoConfirmed(User $objUser): SupportCollection {
        return ($this->model->whereNull("file_path")
                            ->where(BaseModel::STAMP_CREATED_BY, $objUser->user_id)->get());
    }

    public function findTrackByISRC(string $ISRC){
        return ($this->trackModel->where("track_isrc", $ISRC)->first());
    }

    /**
     * @param string $ISRC
     * @return Track|mixed|null
     */
    public function findTrackByISRCAdvanced(string $ISRC){
        $objTracks = $this->trackModel->all();

        foreach ($objTracks as $objTrack) {
            if (strtolower(str_replace("-", "", $objTrack->track_isrc)) == strtolower(str_replace("-", "", $ISRC))) {
                return ($objTrack);
            }
        }

        return null;
    }

    /**
     * @param FileModel $objFile
     * @return array
     */
    public function getParams(FileModel $objFile): array {
        $arrParams = $objFile->makeHidden([BaseModel::STAMP_CREATED, BaseModel::STAMP_UPDATED])->toArray();
        $objSubFile = $objFile->{$objFile->file_category}->makeHidden(["row_uuid", BaseModel::STAMP_CREATED, BaseModel::STAMP_UPDATED]);
        if ($objFile->file_category == "video")
            $objSubFile->makeVisible("music_id");

        $arrSubParams = $objSubFile->toArray();
        array_merge($arrParams, $arrSubParams);

        return ($arrParams);
    }

    /**
     * @param Collection
     * @return \Illuminate\Support\Collection
     */
    public function getTracks(Collection $objCol) {
        return ($this->model->join("soundblock_collections_files", "soundblock_files.file_id", "=", "soundblock_collections_files.file_id")
            ->join("soundblock_tracks", "soundblock_files.file_id", "=", "soundblock_tracks.file_id")
            ->where("soundblock_collections_files.collection_id", $objCol->collection_id)
            ->where("soundblock_files.file_category", "music")
            ->orderBy("soundblock_tracks.track_number", "asc")
            ->get());
    }

    public function getLastIsrc() {
        return $this->trackModel->max(\DB::raw("SUBSTR(track_isrc, 11, 5)"));
    }

    public function getFilesCategories(array $fileUuids) {
        return $this->model->whereIn("file_uuid", $fileUuids)->pluck("file_category")->unique();
    }

    /**
     * @param array $arrFile
     * @param Collection $collection
     * @param User|null $user
     * @return File
     * @throws \Exception
     */
    public function createInCollection(array $arrFile, Collection $collection, ?User $user = null) {
        if (is_null($user)) {
            if (!Auth::user()) {
                throw new \Exception("Invalid Parameter");
            }
            /** @var User */
            $user = Auth::user();
        }

        $arrFile = Util::rename_file($collection, $arrFile);
        $model = $this->createModel($arrFile, $user, $collection);
        $model->collections()->attach($collection->collection_id, [
            "row_uuid"                  => Util::uuid(),
            "collection_uuid"           => $collection->collection_uuid,
            "file_uuid"                 => $model->file_uuid,
            BaseModel::STAMP_CREATED    => time(),
            BaseModel::STAMP_UPDATED    => time(),
            BaseModel::STAMP_CREATED_BY => $user->user_id,
            BaseModel::STAMP_UPDATED_BY => $user->user_id,
        ]);
        $model->save();

        return ($model);
    }

    public function createLedger(FileModel $objFileModel, array $ledgerData) {
        $objLedger = $objFileModel->ledger()->create([
            "ledger_uuid"   => Util::uuid(),
            "ledger_name"   => "",
            "ledger_memo"   => "",
            "qldb_id"       => $ledgerData["document"]["id"],
            "qldb_table"    => "soundblock_files",
            "qldb_block"    => $ledgerData["document"]["blockAddress"],
            "qldb_data"     => $ledgerData["document"]["data"],
            "qldb_hash"     => $ledgerData["document"]["hash"],
            "qldb_metadata" => $ledgerData["document"]["metadata"],
            "table_name"    => $objFileModel->getTable(),
            "table_field"   => $objFileModel->getKeyName(),
            "table_id"      => $objFileModel->getKey(),
        ]);

        $objFileModel->ledger_id = $objLedger->ledger_id;
        $objFileModel->ledger_uuid = $objLedger->ledger_uuid;
        $objFileModel->save();

        return $objLedger;
    }

    /**
     * @param array $arrParams
     * @param User $user
     * @param Collection|null $collection
     * @return FileModel
     * @throws \Exception
     */
    public function createModel(array $arrParams, User $user, Collection $collection) {
        $arrFile = [];
        if (!isset($arrParams["file_category"]))
            throw new \Exception("Invalid parameter", 417);
        $model = new FileModel;

        if (!isset($arrParams[FileModel::STAMP_CREATED_BY])) {
            $arrParams[FileModel::STAMP_CREATED_BY] = $user->user_id;
        }
        $arrParams[FileModel::STAMP_UPDATED_BY] = $user->user_id;

        if (!isset($arrParams[$model->uuid()])) {
            $arrFile[$model->uuid()] = Util::uuid();
        } else {
            $arrFile[$model->uuid()] = $arrParams[$model->uuid()];
        }

        $extCheck = pathinfo($arrParams["file_title"], PATHINFO_EXTENSION);

        if (!empty($extCheck)) {
            str_replace("." . $extCheck, "", $arrParams["file_title"]);
        }

        $arrFile["file_name"] = $arrParams["file_name"];
        $arrFile["file_title"] = trim($arrParams["file_title"]);
        $arrFile["file_path"] = $arrParams["file_path"];
        $arrFile["file_category"] = $arrParams["file_category"];
        $arrFile["file_sortby"] = $arrParams["file_sortby"];
        $arrFile["file_size"] = $arrParams["file_size"];
        $arrFile["file_md5"] = $arrParams["file_md5"];
        $arrFile["file_extension"] = pathinfo($arrParams["file_name"], PATHINFO_EXTENSION);
        $arrFile["ledger_id"] = $arrParams["ledger_id"] ?? null;
        $arrFile["ledger_uuid"] = $arrParams["ledger_uuid"] ?? null;
        $arrFile["directory_id"] = $arrParams["directory_id"] ?? null;
        $arrFile["directory_uuid"] = $arrParams["directory_uuid"] ?? null;
        $arrFile[FileModel::STAMP_CREATED_BY] = $arrParams[FileModel::STAMP_CREATED_BY];
        $arrFile[FileModel::STAMP_UPDATED_BY] = $arrParams[FileModel::STAMP_UPDATED_BY];

        $model->fill($arrFile);
        $model->save();

        $fileCategory = Util::lowerLabel($arrParams["file_category"]);
        switch ($fileCategory) {
            case "music":
            {
                $this->insertMusicRecord($model, $arrParams, $collection);
                break;
            }
            case "video":
            {
                $this->insertVideoRecord($model, $arrParams);
                break;
            }
            case "merch":
            {
                $this->insertMerchRecord($model, $arrParams);
                break;
            }
            case "files":
            {
                $this->insertFilesRecord($model, $arrParams);
                break;
            }
            default:
                break;
        }

        return ($model);
    }

    public function insertMusicRecord(FileModel $objFile, array $arrFile, Collection $collection = null) {
        $arrMusic = [];
        $model = new Track;

        $arrMusic[$model->uuid()] = Util::uuid();
        $arrMusic["file_id"] = $objFile->file_id;
        $arrMusic["file_uuid"] = $objFile->file_uuid;
        $arrMusic["remote_addr"] = $arrFile["remote_addr"];
        $arrMusic["remote_host"] = $arrFile["remote_host"];
        $arrMusic["remote_agent"] = $arrFile["remote_agent"];
        $arrFile["track_number"] = intval($arrFile["track_number"]);

        if (is_int($arrFile["track_number"]) && $arrFile["track_number"] != 0) {
            $arrMusic["track_number"] = $arrFile["track_number"];
        } else {
            throw new \Exception("File track must be integer.");
        }

        $arrMusic["track_duration"] = $arrFile["track_duration"];
        $arrMusic[Track::STAMP_CREATED_BY] = $arrFile[Track::STAMP_CREATED_BY];
        $arrMusic[Track::STAMP_UPDATED_BY] = $arrFile[Track::STAMP_UPDATED_BY];

        if (empty($arrFile["copyright_name"])) {
            $arrMusic["copyright_name"] = $collection ? $collection->project->project_copyright_name : "";
        } else {
            $arrMusic["copyright_name"] = $arrFile["copyright_name"];
        }

        if (empty($arrFile["copyright_year"])) {
            $arrMusic["copyright_year"] = $collection ? $collection->project->project_copyright_year : "";
        } else {
            $arrMusic["copyright_year"] = $arrFile["copyright_year"];
        }

        if (empty($arrFile["recording_location"])) {
            $arrMusic["recording_location"] = $collection ? $collection->project->project_recording_location : "";
        } else {
            $arrMusic["recording_location"] = $arrFile["recording_location"];
        }

        if (empty($arrFile["recording_year"])) {
            $arrMusic["recording_year"] = $collection ? $collection->project->project_recording_year : "";
        } else {
            $arrMusic["recording_year"] = $arrFile["recording_year"];
        }

        if (empty($arrFile["track_language_audio"])) {
            $arrMusic["track_language_audio_id"] = $collection ? $collection->project->project_language_id : "";
            $arrMusic["track_language_audio_uuid"] = $collection ? $collection->project->project_language_uuid : "";
        } else {
            $objLang = $this->languagesRepo->find($arrFile["track_language_audio"]);

            if (is_null($objLang)) {
                throw new \Exception("Invalid language.");
            }

            $arrMusic["track_language_audio_id"] = $objLang->data_id;
            $arrMusic["track_language_audio_uuid"] = $objLang->data_uuid;
        }

        if (!empty($arrFile["track_language_metadata"])) {
            $objLang = $this->languagesRepo->find($arrFile["track_language_metadata"]);

            if (is_null($objLang)) {
                throw new \Exception("Invalid language.");
            }

            $arrMusic["track_language_metadata_id"] = $objLang->data_id;
            $arrMusic["track_language_metadata_uuid"] = $objLang->data_uuid;
        }

        if (intval($arrFile["track_volume_number"]) > intval($collection ? $collection->project->project_volumes : 10)) {
            throw new \Exception("Invalid track volume number.");
        } else {
            $arrMusic["track_volume_number"] = $arrFile["track_volume_number"];
        }

        if (empty($arrFile["track_release_date"])) {
            $arrMusic["track_release_date"] = $collection ? $collection->project->project_date : "";
        } else {
            $arrMusic["track_release_date"] = $arrFile["track_release_date"];
        }

        if (isset($arrFile["genre_primary"])) {
            $objPrimaryGenre = $this->genresRepo->findByUuid($arrFile["genre_primary"], true, false);

            if (is_null($objPrimaryGenre)) {
                throw new \Exception("Genre is not primary.");
            }

            $arrMusic["genre_primary_id"] = $objPrimaryGenre->data_id;
            $arrMusic["genre_primary_uuid"] = $objPrimaryGenre->data_uuid;
        } else {
            throw new \Exception("Primary genre field is required.");
        }

        if (isset($arrFile["genre_secondary"])) {
            $objSecondaryGenre = $this->genresRepo->findByUuid($arrFile["genre_secondary"], false, true);

            if (is_null($objSecondaryGenre)) {
                throw new \Exception("Genre is not secondary.");
            }

            $arrMusic["genre_secondary_id"] = $objSecondaryGenre->data_id;
            $arrMusic["genre_secondary_uuid"] = $objSecondaryGenre->data_uuid;
        }

        if (!empty($arrFile["track_isrc"])) {
            if (strlen($arrFile["track_isrc"]) == 12 && substr($arrFile["track_isrc"], 0, 2) == "US") {
                $arrFile["track_isrc"] = strtoupper(substr($arrFile["track_isrc"], 0, 2) . "-"
                    . substr($arrFile["track_isrc"], 2, 3) . "-"
                    . substr($arrFile["track_isrc"], 5, 2) . "-"
                    . substr($arrFile["track_isrc"], 7, 5));
            }
            if (is_object($this->findTrackByISRC($arrFile["track_isrc"]))) {
                throw new \Exception("ISRC code already in use.");
            }

            $objIsrc = $this->isrcService->findByIsrc($arrFile["track_isrc"]);

            if (is_object($objIsrc)) {
                if ($objIsrc->flag_assigned == true){
                    throw new \Exception("ISRC code already assigned.");
                }

                $this->isrcService->useIsrc($objIsrc);
            }
        }

        $arrMusic["track_isrc"]                  = $arrFile["track_isrc"] ?? null;
        $arrMusic["track_artist"]                = $arrFile["track_artist"] ?? null;
        $arrMusic["track_version"]               = $arrFile["track_version"] ?? null;
        $arrMusic["country_recording"]           = $arrFile["country_recording"] ?? null;
        $arrMusic["country_commissioning"]       = $arrFile["country_commissioning"] ?? null;
        $arrMusic["rights_holder"]               = $arrFile["rights_holder"] ?? null;
        $arrMusic["rights_owner"]                = $arrFile["rights_owner"] ?? null;
        $arrMusic["rights_contract"]             = $arrFile["rights_contract"] ?? null;
        $arrMusic["flag_track_explicit"]         = $arrFile["flag_track_explicit"];
        $arrMusic["flag_track_instrumental"]     = $arrFile["flag_track_instrumental"];
        $arrMusic["flag_allow_preorder"]         = $arrFile["flag_allow_preorder"];
        $arrMusic["flag_allow_preorder_preview"] = $arrFile["flag_allow_preorder_preview"];

        $model->fill($arrMusic);
        $model->save();

        if (isset($arrFile["artists"])) {
            foreach ($arrFile["artists"] as $artist) {
                $objArtist = $this->artistService->find($artist["artist"]);

                if (is_null($objArtist)) {
                    $objArtist = $this->artistService->create($artist["artist"], $collection->project->account);
                }

                if (!$model->artists()->where("soundblock_artists.artist_uuid", $objArtist->artist_uuid)->exists()){
                    $model->artists()->attach($objArtist->artist_id, [
                        "row_uuid" => Util::uuid(),
                        "artist_id" => $objArtist->artist_id,
                        "artist_uuid" => $objArtist->artist_uuid,
                        "track_id" => $model->track_id,
                        "track_uuid" => $model->track_uuid,
                        "file_id" => $model->file_id,
                        "file_uuid" => $model->file_uuid,
                        "artist_type" => $artist["type"]
                    ]);
                }
            }
        }

        if (isset($arrFile["contributors"])) {
            foreach ($arrFile["contributors"] as $arrayContributor) {
                $objContributor = $this->contributorRepo->find($arrayContributor["contributor"]);

                if ($objContributor) {
                    foreach ($arrayContributor["types"] as $type) {
                        $objContributorRole = $this->contributorsRoleRepo->find($type);

                        if ($objContributorRole) {
                            $model->contributors()->attach($objContributor->contributor_id, [
                                "row_uuid" => Util::uuid(),
                                "contributor_id" => $objContributor->contributor_id,
                                "contributor_uuid" => $objContributor->contributor_uuid,
                                "contributor_role_id" => $objContributorRole->data_id,
                                "contributor_role_uuid" => $objContributorRole->data_uuid,
                                "track_id" => $model->track_id,
                                "track_uuid" => $model->track_uuid,
                                "file_id" => $model->file_id,
                                "file_uuid" => $model->file_uuid,
                                BaseModel::STAMP_CREATED => Util::current_time(),
                                BaseModel::STAMP_CREATED_BY => 1,
                                BaseModel::STAMP_UPDATED => Util::current_time(),
                                BaseModel::STAMP_UPDATED_BY => 1,
                            ]);
                        }
                    }
                }
            }
        }

        if (isset($arrFile["publishers"])) {
            foreach ($arrFile["publishers"] as $arrayPublisher) {
                $objPublisher = $this->artistPublisherRepo->find($arrayPublisher["publisher"]);

                if ($objPublisher) {
                    $model->publisher()->attach($objPublisher->publisher_id, [
                        "row_uuid" => Util::uuid(),
                        "publisher_id" => $objPublisher->publisher_id,
                        "publisher_uuid" => $objPublisher->publisher_uuid,
                        "track_id" => $model->track_id,
                        "track_uuid" => $model->track_uuid,
                        "file_id" => $model->file_id,
                        "file_uuid" => $model->file_uuid
                    ]);
                }
            }
        }
        $model->refresh();

        /* Insert Track History Records */
        $arrHistory = $arrMusic;
        unset(
            $arrHistory["file_id"],
            $arrHistory["file_uuid"],
            $arrHistory["track_uuid"],
            $arrHistory[Track::STAMP_CREATED_BY],
            $arrHistory[Track::STAMP_UPDATED_BY],
            $arrHistory["track_language_metadata_id"],
            $arrHistory["track_language_metadata_uuid"],
            $arrHistory["track_language_audio_id"],
            $arrHistory["track_language_audio_uuid"],
            $arrHistory["genre_primary_id"],
            $arrHistory["genre_primary_uuid"],
            $arrHistory["genre_secondary_id"],
            $arrHistory["genre_secondary_uuid"],
        );
        $arrHistory["track_language_metadata"] = optional($model->languageMetadata)->data_language;
        $arrHistory["track_language_audio"] = optional($model->languageAudio)->data_language;
        $arrHistory["primary_genre"] = $model->primaryGenre->data_genre;
        $arrHistory["primary_genre"] = optional($model->secondaryGenre)->data_genre;

        if (!empty($model->artists)) {
            $arrHistory["artists"] = json_encode($model->artists);
        }

        if (!empty($model->contributors)) {
            $arrHistory["contributors"] = json_encode($model->contributors);
        }

        if (!empty($model->publishers)) {
            $arrHistory["publishers"] = json_encode($model->publisher);
        }

        $arrHistory = array_filter($arrHistory);

        foreach ($arrHistory as $column => $value) {
            $this->trackHistoryRepo->create([
                "track_id" => $model->track_id,
                "track_uuid" => $model->track_uuid,
                "field_name" => $column,
                "old_value" => null,
                "new_value" => $value,
                Track::STAMP_CREATED_BY => $model[Track::STAMP_CREATED_BY],
                Track::STAMP_UPDATED_BY => $model[Track::STAMP_UPDATED_BY]
            ]);
        }

        return ($model);
    }

    public function insertVideoRecord(FileModel $objFile, array $arrFile) {
        $arrVideo = [];
        $model = new FileVideo;

        $arrVideo[$model->uuid()] = Util::uuid();
        $arrVideo["file_id"] = $objFile->file_id;
        $arrVideo["file_uuid"] = $objFile->file_uuid;
        if (isset($arrFile["music_id"]) && isset($arrFile["music_uuid"])) {
            $arrVideo["music_id"] = $arrFile["music_id"];
            $arrVideo["music_uuid"] = $arrFile["music_uuid"];
        }
        if (isset($arrFile["file_isrc"])) {
            $arrVideo["file_isrc"] = $arrFile["file_isrc"];
        }

        $arrVideo[FileVideo::STAMP_CREATED_BY] = $arrFile[FileModel::STAMP_CREATED_BY];
        $arrVideo[FileVideo::STAMP_UPDATED_BY] = $arrFile[FileModel::STAMP_UPDATED_BY];

        $model->fill($arrVideo);
        $model->save();

        return ($model);
    }

    public function insertMerchRecord(FileModel $objFile, array $arrFile) {
        $arrMerch = [];
        $model = new FileMerch;

        $arrMerch[$model->uuid()] = Util::uuid();
        $arrMerch["file_id"] = $objFile->file_id;
        $arrMerch["file_uuid"] = $objFile->file_uuid;

        if (isset($arrFile["file_sku"])) {
            $arrMerch["file_sku"] = $arrFile["file_sku"];
        }

        $arrMerch[FileMerch::STAMP_CREATED_BY] = $arrFile[FileMerch::STAMP_CREATED_BY];
        $arrMerch[FileMerch::STAMP_UPDATED_BY] = $arrFile[FileMerch::STAMP_UPDATED_BY];
        $model->fill($arrMerch);
        $model->save();

        return ($model);
    }

    public function insertFilesRecord(FileModel $objFile, array $arrFile) {
        $arrOther = [];
        $model = new FileOther;

        $arrOther[$model->uuid()] = Util::uuid();
        $arrOther["file_id"] = $objFile->file_id;
        $arrOther["file_uuid"] = $objFile->file_uuid;

        $arrMerch[FileOther::STAMP_CREATED_BY] = $arrFile[FileOther::STAMP_CREATED_BY];
        $arrMerch[FileOther::STAMP_UPDATED_BY] = $arrFile[FileOther::STAMP_UPDATED_BY];
        $model->fill($arrOther);
        $model->save();

        return ($model);
    }

    public function create(array $arrParams) {
        $arrFile = [];
        if (!isset($arrParams["file_category"]))
            throw new \Exception("Invalid Parameter", 417);
        $model = $this->model->newInstance();

        if (!isset($arrParams[$model->uuid()])) {
            $arrFile[$model->uuid()] = Util::uuid();
        } else {
            $arrFile[$model->uuid()] = $arrParams[$model->uuid()];
        }

        $arrFile["file_name"] = $arrParams["file_name"];
        $arrFile["file_title"] = $arrParams["file_title"];
        $arrFile["file_path"] = $arrParams["file_path"];
        $arrFile["file_category"] = $arrParams["file_category"];
        $arrFile["file_sortby"] = $arrParams["file_sortby"];
        $arrFile["file_size"] = $arrParams["file_size"];
        $arrFile["file_md5"] = $arrParams["file_md5"];
        $arrFile["remote_addr"] = $arrParams["remote_addr"];
        $arrFile["remote_host"] = $arrParams["remote_host"];
        $arrFile["remote_agent"] = $arrParams["remote_agent"];
        $arrFile["file_extension"] = pathinfo($arrParams["file_name"], PATHINFO_EXTENSION);

        $model->fill($arrFile);
        $model->save();

        $fileCategory = Util::lowerLabel($arrParams["file_category"]);
        switch ($fileCategory) {
            case "music":
            {
                $this->insertMusicRecord($model, $arrParams);
                break;
            }
            case "video":
            {
                $this->insertVideoRecord($model, $arrParams);
                break;
            }
            case "merch":
            {
                $this->insertMerchRecord($model, $arrParams);
                break;
            }
            case "files":
            {
                $this->insertFilesRecord($model, $arrParams);
                break;
            }
            default:
                break;
        }

        return ($model);
    }

    /**
     * @param $model
     * @param array $arrParams
     * @return File
     */
    public function update($model, array $arrParams) {
        $updatableFields = ["file_name", "file_title", "file_path", "file_category", "file_sortby"];
        $arrFile = Util::array_with_key($updatableFields, $arrParams);
        $model->fill($arrFile);
        $model->save();
        $model = $this->updateSub($model, $arrParams);

        return ($model);
    }

    /**
     * @param FileModel $objFile
     * @param array $arrParams
     * @return
     */
    protected function updateSub(FileModel $objFile, array $arrParams): File {
        $category = $objFile->file_category;
        switch ($category) {
            case "music" :
            {
                $updatableFields = ["track_number", "track_isrc"];
                break;
            }
            case "video" :
            {
                $updatableFields = ["music_id", "music_uuid", "file_isrc"];
                break;
            }
            case "merch" :
            {
                $updatableFields = ["file_sku"];
                break;
            }
            case "files" :
                {
                    break;
                }
                break;
        }
        $arrFileSub = Util::array_with_key($updatableFields, $arrParams);

        $objFile->{$category}->fill($arrFileSub);
        $objFile->{$category}->save();
        return ($objFile);
    }
}
