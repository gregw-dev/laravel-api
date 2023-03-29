<?php

namespace App\Services\Music\AllMusic;

use App\Models\Music\Project\{
    ProjectTrackComposer,
    ProjectTrackFeature,
    ProjectTrackPerformer
};

class AllMusicUpdateTracksTables
{
    /** @var AllMusicInsert */
    private $allMusicInsert;
    /** @var ProjectTrackComposer */
    private $tracksComposer;
    /** @var ProjectTrackFeature */
    private $tracksFeature;
    /** @var ProjectTrackPerformer */
    private $tracksPerformer;

    /**
     * AllMusicUpdateTracksTables constructor.
     * @param AllMusicInsert $allMusicInsert
     * @param ProjectTrackComposer $tracksComposer
     * @param ProjectTrackFeature $tracksFeature
     * @param ProjectTrackPerformer $tracksPerformer
     */
    public function __construct(AllMusicInsert $allMusicInsert, ProjectTrackComposer $tracksComposer,
                                ProjectTrackFeature $tracksFeature, ProjectTrackPerformer $tracksPerformer){
        $this->allMusicInsert = $allMusicInsert;
        $this->tracksComposer = $tracksComposer;
        $this->tracksFeature = $tracksFeature;
        $this->tracksPerformer = $tracksPerformer;
    }

    public function setupComposers(){
        $objCollection = $this->tracksComposer->where("artist_id", 0)->get();

        foreach ($objCollection as $row) {
            $result = $this->allMusicInsert->getArtistByUrl($row["url_allmusic"]);

            if (isset($result)) {
                $this->tracksComposer->where("composer_id", $row["composer_id"])->update([
                    "artist_id" => $result->artist_id,
                    "artist_uuid" => $result->artist_uuid
                ]);
            }
        }

        return;
    }

    public function setupFeatures(){
        $objCollection = $this->tracksFeature->where("artist_id", 0)->get();

        foreach ($objCollection as $row) {
            $result = $this->allMusicInsert->getArtistByUrl($row["url_allmusic"]);

            if (isset($result)) {
                $this->tracksFeature->where("featuring_id", $row["featuring_id"])->update([
                    "artist_id" => $result->artist_id,
                    "artist_uuid" => $result->artist_uuid
                ]);
            }
        }

        return;
    }

    public function setupPerformers(){
        $objCollection = $this->tracksPerformer->where("artist_id", 0)->get();

        foreach ($objCollection as $row) {
            $result = $this->allMusicInsert->getArtistByUrl($row["url_allmusic"]);

            if (isset($result)) {
                $this->tracksPerformer->where("performer_id", $row["performer_id"])->update([
                    "artist_id" => $result->artist_id,
                    "artist_uuid" => $result->artist_uuid
                ]);
            }
        }

        return;
    }
}
