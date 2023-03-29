<?php

namespace App\Services\Music\AllMusic;

use App\Helpers\Util;
use Str;
use DateTime;
use Exception;
use App\Jobs\Music\GetTracksDataFromAllMusic;
use App\Models\Music\Maps\{
    Project as MapsProject,
    Track as MapsTrack,
};
use App\Models\Music\Artist\{
    Artist,
    ArtistAlias,
    ArtistGenre,
    ArtistInfluenced,
    ArtistMember,
    ArtistMood,
    ArtistRelated,
    ArtistSimilar,
    ArtistStyle,
    ArtistTheme
};
use App\Models\Music\Project\{
    Project,
    ProjectGenre,
    ProjectMood,
    ProjectRating,
    ProjectStyle,
    ProjectTheme,
    ProjectTrack,
    ProjectTrackComposer,
    ProjectTrackFeature,
    ProjectTrackPerformer
};

class AllMusicInsert
{
    /** @var Artist */
    private Artist $artist;
    /** @var ArtistAlias */
    private ArtistAlias $artistAlias;
    /** @var ArtistGenre */
    private ArtistGenre $artistGenre;
    /** @var ArtistInfluenced */
    private ArtistInfluenced $artistInfluenced;
    /** @var ArtistMember */
    private ArtistMember $artistMembers;
    /** @var ArtistMood */
    private ArtistMood $artistMood;
    /** @var ArtistRelated */
    private ArtistRelated $artistRelated;
    /** @var ArtistSimilar */
    private ArtistSimilar $artistSimilar;
    /** @var ArtistStyle */
    private ArtistStyle $artistStyle;
    /** @var ArtistTheme */
    private ArtistTheme $artistTheme;
    /** @var Project */
    private Project $project;
    /** @var ProjectGenre */
    private ProjectGenre $projectGenre;
    /** @var ProjectMood */
    private ProjectMood $projectMood;
    /** @var ProjectStyle */
    private ProjectStyle $projectStyle;
    /** @var ProjectTheme */
    private ProjectTheme $projectTheme;
    /** @var ProjectTrack */
    private ProjectTrack $projectTrack;
    /** @var ProjectTrackComposer */
    private ProjectTrackComposer $tracksComposer;
    /** @var ProjectTrackFeature */
    private ProjectTrackFeature $tracksFeature;
    /** @var ProjectTrackPerformer */
    private ProjectTrackPerformer $tracksPerformer;
    /** @var AllMusicScrape */
    private AllMusicScrape $allMusicScrape;
//    /** @var StylesAllmusic */
//    private StylesAllmusic $stylesAllmusic;
    /** @var MapsProject */
    private MapsProject $mapsProject;
    /** @var MapsTrack */
    private MapsTrack $mapsTrack;
    /** @var ProjectRating */
    private ProjectRating $projectRating;

    /**
     * AllMusic constructor.
     * @param Artist $artist
     * @param ArtistAlias $artistAlias
     * @param ArtistGenre $artistGenre
     * @param ArtistInfluenced $artistInfluenced
     * @param ArtistMember $artistMembers
     * @param ArtistMood $artistMood
     * @param ArtistRelated $artistRelated
     * @param ArtistSimilar $artistSimilar
     * @param ArtistStyle $artistStyle
     * @param ArtistTheme $artistTheme
     * @param Project $project
     * @param ProjectGenre $projectGenre
     * @param ProjectMood $projectMood
     * @param ProjectStyle $projectStyle
     * @param ProjectTheme $projectTheme
     * @param ProjectTrack $projectTrack
     * @param ProjectTrackComposer $tracksComposer
     * @param ProjectTrackFeature $tracksFeature
     * @param ProjectTrackPerformer $tracksPerformer
     * @param AllMusicScrape $allMusicScrape
//     * @param StylesAllmusic $stylesAllmusic
     * @param MapsProject $mapsProject
     * @param MapsTrack $mapsTrack
     * @param ProjectRating $projectRating
     */
    public function __construct(Artist $artist, ArtistAlias $artistAlias, ArtistGenre $artistGenre,
                                ArtistInfluenced $artistInfluenced, ArtistMember $artistMembers, ArtistMood $artistMood,
                                ArtistRelated $artistRelated, ArtistSimilar $artistSimilar, ArtistStyle $artistStyle,
                                ArtistTheme $artistTheme, Project $project, ProjectGenre $projectGenre,
                                ProjectMood $projectMood, ProjectStyle $projectStyle, ProjectTheme $projectTheme,
                                ProjectTrack $projectTrack, ProjectTrackComposer $tracksComposer,
                                ProjectTrackFeature $tracksFeature, ProjectTrackPerformer $tracksPerformer,
                                AllMusicScrape $allMusicScrape,   ProjectRating $projectRating, MapsProject $mapsProject,
                                MapsTrack $mapsTrack){

        $this->artist           = $artist;
        $this->artistAlias      = $artistAlias;
        $this->artistGenre      = $artistGenre;
        $this->artistInfluenced = $artistInfluenced;
        $this->artistMembers    = $artistMembers;
        $this->artistMood       = $artistMood;
        $this->artistRelated    = $artistRelated;
        $this->artistSimilar    = $artistSimilar;
        $this->artistStyle      = $artistStyle;
        $this->artistTheme      = $artistTheme;
        $this->project          = $project;
        $this->projectGenre     = $projectGenre;
        $this->projectMood      = $projectMood;
        $this->projectStyle     = $projectStyle;
        $this->projectTheme     = $projectTheme;
        $this->projectTrack     = $projectTrack;
        $this->tracksComposer   = $tracksComposer;
        $this->tracksFeature    = $tracksFeature;
        $this->tracksPerformer  = $tracksPerformer;
        $this->allMusicScrape   = $allMusicScrape;
//        $this->stylesAllmusic   = $stylesAllmusic;
        $this->mapsProject      = $mapsProject;
        $this->mapsTrack        = $mapsTrack;
        $this->projectRating    = $projectRating;
//        $this->genre            = $genre;
//        $this->mood             = $mood;
//        $this->style            = $style;
//        $this->theme            = $theme;
    }

    public function insertArtistToDb($arrayParams){
        /* Check if Artist Already Isset in Table */
        $objArtist = $this->getArtistByUrl($arrayParams["artists"]["url_allmusic"]);

        if (!isset($objArtist)) {
            /* Insert Data Into Artists Table */
            try {
                $objArtist = $this->artist->create([
                    "artist_uuid" => strtoupper(Str::uuid()),
                    "arena_id" => "",
                    "artist_name" => $arrayParams["artists"]["artist_name"],
                    "artist_active" => $arrayParams["artists"]["artist_active"],
                    "artist_born" => $arrayParams["artists"]["artist_born"],
                    "stamp_epoch" => time(),
                    "stamp_date" => date("Y-m-d"),
                    "stamp_time" => date("G:i:s"),
                    "url_allmusic" => $arrayParams["artists"]["url_allmusic"],
                    "url_amazon" => "",
                    "url_itunes" => "",
                    "url_lastfm" => "",
                    "url_spotify" => "",
                    "url_wikipedia" => "",
                    "flag_allmusic" => "Y",
                    "flag_amazon" => "N",
                    "flag_itunes" => "N",
                    "flag_lastfm" => "N",
                    "flag_spotify" => "N",
                    "flag_wikipedia" => "N",
                ]);
            } catch (Exception $e) {
                info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 201");
                throw $e;
            }
        }

        /* Insert Data Into Artists Aliases Table */
        try {
            if ( !empty($arrayParams["artist_aliases"]) && $this->artistAlias->where("artist_id", $objArtist->artist_id)->get()->isEmpty() ) {
                foreach ($arrayParams["artist_aliases"] as $alias) {
                    $this->artistAlias->create([
                        "row_uuid" => strtoupper(Str::uuid()),
                        "artist_id" => $objArtist->artist_id,
                        "artist_uuid" => $objArtist->artist_uuid,
                        "artist_alias" => $alias,
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC"
                    ]);
                }
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 223");
        }

        /* Insert Data Into Artists Genres Table */
        try {
            if ( !empty($arrayParams["artist_genres"]) && $this->artistGenre->where("artist_id", $objArtist->artist_id)->get()->isEmpty() ) {
                foreach ($arrayParams["artist_genres"] as $genre) {
//                    $objGenre = $this->genre->where("genre_name", $genre)->first();
                    $this->artistGenre->create([
                        "row_uuid" => strtoupper(Str::uuid()),
                        "artist_id" => $objArtist->artist_id,
                        "artist_uuid" => $objArtist->artist_uuid,
                        "artist_genre" => $genre,
//                        "genre_id" => $objGenre ? $objGenre->genre_id : 0,
//                        "genre_uuid" => $objGenre ? $objGenre->genre_uuid : "",
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC"
                    ]);
                }
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 245");
        }

        /* Insert Data Into Artists Influenced Table */
        try {
            if ( !empty($arrayParams["artist_influenced"]) && $this->artistInfluenced->where("artist_id", $objArtist->artist_id)->get()->isEmpty() ) {
                foreach ($arrayParams["artist_influenced"] as $influence) {
                    $this->artistInfluenced->create([
                        "row_uuid" => strtoupper(Str::uuid()),
                        "artist_id" => $objArtist->artist_id,
                        "artist_uuid" => $objArtist->artist_uuid,
                        "artist_influence" => $influence["artist_influence"],
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC",
                        "url_allmusic" => $influence["url_allmusic"]
                    ]);
                }
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 266");
        }

        /* Insert Data Into Artists Members Table */
        try {
            if ( !empty($arrayParams["artist_members"]) && $this->artistMembers->where("artist_id", $objArtist->artist_id)->get()->isEmpty() ) {
                foreach ($arrayParams["artist_members"] as $member) {
                    $this->artistMembers->create([
                        "row_uuid" => strtoupper(Str::uuid()),
                        "artist_id" => $objArtist->artist_id,
                        "artist_uuid" => $objArtist->artist_uuid,
                        "artist_member" => $member["artist_member"],
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC",
                        "url_allmusic" => $member["url_allmusic"]
                    ]);
                }
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 287");
        }

        /* Insert Data Into Artists Moods Table */
        try {
            if ( !empty($arrayParams["artist_moods"]) && $this->artistMood->where("artist_id", $objArtist->artist_id)->get()->isEmpty() ) {
                foreach ($arrayParams["artist_moods"] as $mood) {
//                    $objMood = $this->mood->where("mood_name", $mood)->first();
                    $this->artistMood->create([
                        "row_uuid" => strtoupper(Str::uuid()),
                        "artist_id" => $objArtist->artist_id,
                        "artist_uuid" => $objArtist->artist_uuid,
                        "artist_mood" => $mood,
//                        "mood_id" => $objMood ? $objMood->mood_id : 0,
//                        "mood_uuid" => $objMood ? $objMood->mood_uuid : "",
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC"
                    ]);
                }
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 309");
        }

        /* Insert Data Into Artists Related Table */
        try {
            if ( $this->artistRelated->where("artist_id", $objArtist->artist_id)->get()->isEmpty() ) {
                $arrRelated = array_merge((array)$arrayParams["artist_similar"], (array)$arrayParams["artist_influenced"]);
                foreach ($arrRelated as $related) {
                    $objRelated = $this->getArtistByUrl($related["url_allmusic"]);
                    if (!isset($objRelated)) {
                        [$arrayRelatedArtistParams, $strArtistCurl, $strArtistRelatedCurl] = $this->allMusicScrape->scrapeArtistPage($related["url_allmusic"]);
                        $objRelated = $this->insertArtistWithoutRelated($arrayRelatedArtistParams);
                    }
                    $this->artistRelated->create([
                        "row_uuid" => strtoupper(Str::uuid()),
                        "artist_id" => $objArtist->artist_id,
                        "artist_uuid" => $objArtist->artist_uuid,
                        "related_id" => $objRelated->artist_id,
                        "related_uuid" => $objRelated->artist_uuid,
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC",
                        "flag_influence" => $related["flag"] == "influenced" ? "Y" : "N",
                        "flag_similarity" => $related["flag"] == "similar" ? "Y" : "N"
                    ]);
                }
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 338");
        }

        /* Insert Data Into Artists Similar Table */
        try {
            if ( !empty($arrayParams["artist_similar"]) && $this->artistSimilar->where("artist_id", $objArtist->artist_id)->get()->isEmpty() ) {
                foreach ($arrayParams["artist_similar"] as $similar) {
                    $this->artistSimilar->create([
                        "row_uuid" => strtoupper(Str::uuid()),
                        "artist_id" => $objArtist->artist_id,
                        "artist_uuid" => $objArtist->artist_uuid,
                        "artist_similar" => $similar["artist_similar"],
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC",
                        "url_allmusic" => $similar["url_allmusic"]
                    ]);
                }
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 359");
        }

        /* Insert Data Into Artists Styles Table */
        try {
            if ( !empty($arrayParams["artist_styles"]) && $this->artistStyle->where("artist_id", $objArtist->artist_id)->get()->isEmpty() ) {
                foreach ($arrayParams["artist_styles"] as $style) {
//                    $styleMatch = $this->stylesAllmusic->where("style_name", $style)->value("style_match");
                    $styleMatch = null;
//                    $objStyle = $this->style->where("style_name", $style)->first();

                    $this->artistStyle->create([
                        "row_uuid" => strtoupper(Str::uuid()),
                        "artist_id" => $objArtist->artist_id,
                        "artist_uuid" => $objArtist->artist_uuid,
                        "artist_style" => $style,
//                        "style_id" => $objStyle ? $objStyle->style_id : 0,
//                        "style_uuid" => $objStyle ? $objStyle->style_uuid : "",
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC",
                        "flag_status" => is_null($styleMatch)? "" : strtoupper($styleMatch)
                    ]);
                }
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 384");
        }

        /* Insert Data Into Artists Themes Table */
        try {
            if ( !empty($arrayParams["artist_themes"]) && $this->artistTheme->where("artist_id", $objArtist->artist_id)->get()->isEmpty() ) {
                foreach ($arrayParams["artist_themes"] as $theme) {
//                    $objTheme = $this->theme->where("theme_name", $theme)->first();
                    $this->artistTheme->create([
                        "row_uuid" => strtoupper(Str::uuid()),
                        "artist_id" => $objArtist->artist_id,
                        "artist_uuid" => $objArtist->artist_uuid,
                        "artist_theme" => $theme,
//                        "theme_id" => $objTheme ? $objTheme->theme_id : 0,
//                        "theme_uuid" => $objTheme ? $objTheme->theme_uuid : "",
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC"
                    ]);
                }
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 406");
        }

        return ($objArtist);
    }

    public function insertProjectToDb($arrayParams, $objArtist){
        /* Check if PROJECT Already Isset in Table */
        $objProject = $this->getProjectByUrl($arrayParams["projects"]["url_allmusic"]);

        if (!isset($objProject)) {
            /* Insert Data Into Projects Table */
            try {
                $date = new DateTime($arrayParams["projects"]["project_date"]);
                $projectDate = $date->format("Y-m-d");

                $objProject = $this->project->create([
                    "project_uuid" => strtoupper(Str::uuid()),
                    "artist_id" => $objArtist->artist_id,
                    "artist_uuid" => $objArtist->artist_uuid,
                    "project_type" => $arrayParams["projects"]["project_type"],
                    "project_date" => $projectDate,
                    "project_year" => $arrayParams["projects"]["project_year"],
                    "project_name" => $arrayParams["projects"]["project_name"],
                    "project_label" => $arrayParams["projects"]["project_label"],
                    "project_duration" => $arrayParams["projects"]["project_duration"],
                    "rating_value" => $arrayParams["projects_rating"] ? floatval($arrayParams["projects_rating"]["rating"]) : 0.00,
                    "rating_count" => $arrayParams["projects_rating"] ? intval($arrayParams["projects_rating"]["user_rating_count"]) : 0,
                    "stamp_epoch" => time(),
                    "stamp_date" => date("Y-m-d"),
                    "stamp_time" => date("G:i:s"),
                    "stamp_source" => "ALLMUSIC",
                    "url_allmusic" => $arrayParams["projects"]["url_allmusic"],
                    "url_amazon" => "",
                    "url_itunes" => "",
                    "url_spotify" => "",
                    "flag_allmusic" => "Y"
                ]);
            } catch (Exception $e) {
                info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 445");
            }
        }

        /* Insert Data Into Projects Genres Table */
        try {
            if ( !empty($arrayParams["projects_genre"]) && $this->projectGenre->where("project_id", $objProject->project_id)->get()->isEmpty() ) {
                foreach ($arrayParams["projects_genre"] as $genre) {
//                    $objGenre = $this->genre->where("genre_name", $genre)->first();
                    $this->projectGenre->create([
                        "row_uuid" => strtoupper(Str::uuid()),
                        "project_id" => $objProject->project_id,
                        "project_uuid" => $objProject->project_uuid,
                        "project_genre" => $genre,
//                        "genre_id" => $objGenre ? $objGenre->genre_id : 0,
//                        "genre_uuid" => $objGenre ? $objGenre->genre_uuid : "",
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC"
                    ]);
                }
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 468");
        }

        /* Insert Data Into Projects Moods Table */
        try {
            if ( !empty($arrayParams["projects_moods"]) && $this->projectMood->where("project_id", $objProject->project_id)->get()->isEmpty() ) {
                foreach ($arrayParams["projects_moods"] as $mood) {
//                    $objMood = $this->mood->where("mood_name", $mood)->first();
                    $this->projectMood->create([
                        "row_uuid" => strtoupper(Str::uuid()),
                        "project_id" => $objProject->project_id,
                        "project_uuid" => $objProject->project_uuid,
                        "project_mood" => $mood,
//                        "mood_id" => $objMood ? $objMood->mood_id : 0,
//                        "mood_uuid" => $objMood ? $objMood->mood_uuid : "",
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC"
                    ]);
                }
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 490");
        }

        /* Insert Data Into Projects Styles Table */
        try {
            if ( !empty($arrayParams["projects_styles"]) && $this->projectStyle->where("project_id", $objProject->project_id)->get()->isEmpty() ) {
                foreach ($arrayParams["projects_styles"] as $style) {
//                    $objStyle = $this->style->where("style_name", $style)->first();
                    $this->projectStyle->create([
                        "row_uuid" => strtoupper(Str::uuid()),
                        "project_id" => $objProject->project_id,
                        "project_uuid" => $objProject->project_uuid,
                        "project_style" => $style,
//                        "style_id" => $objStyle ? $objStyle->style_id : 0,
//                        "style_uuid" => $objStyle ? $objStyle->style_uuid : "",
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC"
                    ]);
                }
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 512");
        }

        /* Insert Data Into Projects Themes Table */
        try {
            if ( !empty($arrayParams["projects_themes"]) && $this->projectTheme->where("project_id", $objProject->project_id)->get()->isEmpty() ) {
                foreach ($arrayParams["projects_themes"] as $theme) {
//                    $objTheme = $this->theme->where("theme_name", $theme)->first();
                    $this->projectTheme->create([
                        "row_uuid" => strtoupper(Str::uuid()),
                        "project_id" => $objProject->project_id,
                        "project_uuid" => $objProject->project_uuid,
                        "project_theme" => $theme,
//                        "theme_id" => $objTheme ? $objTheme->theme_id : 0,
//                        "theme_uuid" => $objTheme ? $objTheme->theme_uuid : "",
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC"
                    ]);
                }
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 534");
        }

        /* Insert Data Into Projects Tracks Table */
        try {
            if ( $this->projectTrack->where("project_id", $objProject->project_id)->get()->isEmpty() ) {
                $arrTracks = [];
                foreach ($arrayParams["projects_tracks"] as $trackNum => $trackValue) {
                    foreach ($arrayParams["projects_tracks"][$trackNum]["stream"] as $stream) {
                        $amazonUrl = $stream["platform"] == "amazon" ? $stream["url"] : "";
                        $spotifyUrl = $stream["platform"] == "spotify" ? $stream["url"] : "";
                    }

                    $objProjectTrack = $this->projectTrack->create([
                        "track_uuid" => strtoupper(Str::uuid()),
                        "project_id" => $objProject->project_id,
                        "project_uuid" => $objProject->project_uuid,
                        "disc_number" => intval($trackValue["disc_number"]) + 1,
                        "track_number" => $trackNum,
                        "track_name" => $trackValue["track_name"],
                        "track_duration" => $trackValue["track_duration"],
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC",
                        "url_allmusic" => $trackValue["url_allmusic"],
                        "url_amazon" => $amazonUrl,
                        "url_spotify" => $spotifyUrl,
                        "flag_allmusic" => "Y"
                    ]);

                    array_push($arrTracks, $objProjectTrack);

                    /* Insert Data Into Projects Tracks Composers Table */
                    if (isset($trackValue["projects_tracks_composers"])) {
                        foreach ($trackValue["projects_tracks_composers"] as $composer) {
                            $this->tracksComposer->create([
                                "composer_uuid" => strtoupper(Str::uuid()),
                                "project_id" => $objProject->project_id,
                                "project_uuid" => $objProject->project_uuid,
                                "track_id" => $objProjectTrack->track_id,
                                "track_uuid" => $objProjectTrack->track_uuid,
                                "admin_id" => 0,
                                "artist_id" => 0,
                                "artist_uuid" => "",
                                "stamp_epoch" => time(),
                                "stamp_date" => date("Y-m-d"),
                                "stamp_time" => date("G:i:s"),
                                "stamp_source" => "ALLMUSIC",
                                "url_allmusic" => $composer
                            ]);
                        }
                    }

                    /* Insert Data Into Projects Tracks Features Table */
                    if (isset($trackValue["projects_tracks_features"])) {
                        foreach ($trackValue["projects_tracks_features"] as $feature) {
                            $this->tracksFeature->create([
                                "featuring_uuid" => strtoupper(Str::uuid()),
                                "project_id" => $objProject->project_id,
                                "project_uuid" => $objProject->project_uuid,
                                "track_id" => $objProjectTrack->track_id,
                                "track_uuid" => $objProjectTrack->track_uuid,
                                "admin_id" => 0,
                                "artist_id" => 0,
                                "artist_uuid" => "",
                                "stamp_epoch" => time(),
                                "stamp_date" => date("Y-m-d"),
                                "stamp_time" => date("G:i:s"),
                                "stamp_source" => "ALLMUSIC",
                                "url_allmusic" => $feature
                            ]);
                        }
                    }

                    /* Insert Data Into Projects Tracks Performers Table */
                    if (isset($trackValue["projects_tracks_performers"])) {
                        foreach ($trackValue["projects_tracks_performers"] as $performer) {
                            $this->tracksPerformer->create([
                                "performer_uuid" => strtoupper(Str::uuid()),
                                "project_id" => $objProject->project_id,
                                "project_uuid" => $objProject->project_uuid,
                                "track_id" => $objProjectTrack->track_id,
                                "track_uuid" => $objProjectTrack->track_uuid,
                                "admin_id" => 0,
                                "artist_id" => 0,
                                "artist_uuid" => "",
                                "stamp_epoch" => time(),
                                "stamp_date" => date("Y-m-d"),
                                "stamp_time" => date("G:i:s"),
                                "stamp_source" => "ALLMUSIC",
                                "url_allmusic" => $performer
                            ]);
                        }
                    }
                }

                dispatch(new GetTracksDataFromAllMusic($arrTracks));
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 634 " . $objProject->project_name);
        }

        return ($objProject);
    }

    protected function insertArtistWithoutRelated($arrayParams){
        /* Insert Data Into Artists Table */
        try {
            $objArtist = $this->artist->create([
                "artist_uuid" => strtoupper(Str::uuid()),
                "arena_id" => "",
                "artist_name" => $arrayParams["artists"]["artist_name"],
                "artist_active" => $arrayParams["artists"]["artist_active"],
                "artist_born" => $arrayParams["artists"]["artist_born"],
                "stamp_epoch" => time(),
                "stamp_date" => date("Y-m-d"),
                "stamp_time" => date("G:i:s"),
                "url_allmusic" => $arrayParams["artists"]["url_allmusic"],
                "url_amazon" => "",
                "url_itunes" => "",
                "url_lastfm" => "",
                "url_spotify" => "",
                "url_wikipedia" => "",
                "flag_allmusic" => "Y",
                "flag_amazon" => "N",
                "flag_itunes" => "N",
                "flag_lastfm" => "N",
                "flag_spotify" => "N",
                "flag_wikipedia" => "N",
            ]);
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 666");
        }

        /* Insert Data Into Artists Aliases Table */
        try {
            if ( !empty($arrayParams["artist_aliases"]) && $this->artistAlias->where("artist_id", $objArtist->artist_id)->get()->isEmpty() ) {
                foreach ($arrayParams["artist_aliases"] as $alias) {
                    $this->artistAlias->create([
                        "row_uuid" => strtoupper(Str::uuid()),
                        "artist_id" => $objArtist->artist_id,
                        "artist_uuid" => $objArtist->artist_uuid,
                        "artist_alias" => $alias,
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC"
                    ]);
                }
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 686");
        }

        /* Insert Data Into Artists Genres Table */
        try {
            if ( !empty($arrayParams["artist_genres"]) && $this->artistGenre->where("artist_id", $objArtist->artist_id)->get()->isEmpty() ) {
                foreach ($arrayParams["artist_genres"] as $genre) {
//                    $objGenre = $this->genre->where("genre_name", $genre)->first();
                    $this->artistGenre->create([
                        "row_uuid" => strtoupper(Str::uuid()),
                        "artist_id" => $objArtist->artist_id,
                        "artist_uuid" => $objArtist->artist_uuid,
                        "artist_genre" => $genre,
//                        "genre_id" => $objGenre ? $objGenre->genre_id : 0,
//                        "genre_uuid" => $objGenre ? $objGenre->genre_uuid : "",
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC"
                    ]);
                }
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 708");
        }

        /* Insert Data Into Artists Influenced Table */
        try {
            if ( !empty($arrayParams["artist_influenced"]) && $this->artistInfluenced->where("artist_id", $objArtist->artist_id)->get()->isEmpty() ) {
                foreach ($arrayParams["artist_influenced"] as $influence) {
                    $this->artistInfluenced->create([
                        "row_uuid" => strtoupper(Str::uuid()),
                        "artist_id" => $objArtist->artist_id,
                        "artist_uuid" => $objArtist->artist_uuid,
                        "artist_influence" => $influence["artist_influence"],
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC",
                        "url_allmusic" => $influence["url_allmusic"]
                    ]);
                }
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 729");
        }

        /* Insert Data Into Artists Members Table */
        try {
            if (isset($arrayParams["artist_members"]) && $this->artistMembers->where("artist_id", $objArtist->artist_id)->get()->isEmpty() ) {
                foreach ($arrayParams["artist_members"] as $member) {
                    $this->artistMembers->create([
                        "row_uuid" => strtoupper(Str::uuid()),
                        "artist_id" => $objArtist->artist_id,
                        "artist_uuid" => $objArtist->artist_uuid,
                        "artist_member" => $member["artist_member"],
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC",
                        "url_allmusic" => $member["url_allmusic"]
                    ]);
                }
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 750");
        }

        /* Insert Data Into Artists Moods Table */
        try {
            if ( !empty($arrayParams["artist_moods"]) && $this->artistMood->where("artist_id", $objArtist->artist_id)->get()->isEmpty() ) {
                foreach ($arrayParams["artist_moods"] as $mood) {
//                    $objMood = $this->mood->where("mood_name", $mood)->first();
                    $this->artistMood->create([
                        "row_uuid" => strtoupper(Str::uuid()),
                        "artist_id" => $objArtist->artist_id,
                        "artist_uuid" => $objArtist->artist_uuid,
                        "artist_mood" => $mood,
//                        "mood_id" => $objMood ? $objMood->mood_id : 0,
//                        "mood_uuid" => $objMood ? $objMood->mood_uuid : "",
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC"
                    ]);
                }
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 772");
        }

        /* Insert Data Into Artists Similar Table */
        try {
            if ( !empty($arrayParams["artist_similar"]) && $this->artistSimilar->where("artist_id", $objArtist->artist_id)->get()->isEmpty() ) {
                foreach ($arrayParams["artist_similar"] as $similar) {
                    $this->artistSimilar->create([
                        "row_uuid" => strtoupper(Str::uuid()),
                        "artist_id" => $objArtist->artist_id,
                        "artist_uuid" => $objArtist->artist_uuid,
                        "artist_similar" => $similar["artist_similar"],
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC",
                        "url_allmusic" => $similar["url_allmusic"]
                    ]);
                }
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 793");
        }

        /* Insert Data Into Artists Styles Table */
        try {
            if ( !empty($arrayParams["artist_styles"]) && $this->artistStyle->where("artist_id", $objArtist->artist_id)->get()->isEmpty() ) {
                foreach ($arrayParams["artist_styles"] as $style) {
//                    $styleMatch = $this->stylesAllmusic->where("style_name", $style)->value("style_match");
                    $styleMatch = null;
//                    $objStyle = $this->style->where("style_name", $style)->first();

                    $this->artistStyle->create([
                        "row_uuid" => strtoupper(Str::uuid()),
                        "artist_id" => $objArtist->artist_id,
                        "artist_uuid" => $objArtist->artist_uuid,
                        "artist_style" => $style,
//                        "style_id" => $objStyle ? $objStyle->style_id : 0,
//                        "style_uuid" => $objStyle ? $objStyle->style_uuid : "",
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC",
                        "flag_status" => is_null($styleMatch)? "" : strtoupper($styleMatch)
                    ]);
                }
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 818");
        }

        /* Insert Data Into Artists Themes Table */
        try {
            if ( !empty($arrayParams["artist_themes"]) && $this->artistTheme->where("artist_id", $objArtist->artist_id)->get()->isEmpty() ) {
                foreach ($arrayParams["artist_themes"] as $theme) {
//                    $objTheme = $this->theme->where("theme_name", $theme)->first();
                    $this->artistTheme->create([
                        "row_uuid" => strtoupper(Str::uuid()),
                        "artist_id" => $objArtist->artist_id,
                        "artist_uuid" => $objArtist->artist_uuid,
                        "artist_theme" => $theme,
//                        "theme_id" => $objTheme ? $objTheme->theme_id : 0,
//                        "theme_uuid" => $objTheme ? $objTheme->theme_uuid : "",
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s"),
                        "stamp_source" => "ALLMUSIC"
                    ]);
                }
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 840");
        }

        return ($objArtist);
    }

    public function insertDataToMaps($objArtist, $objProject){
        try {
            $objMapsProject = $this->mapsProject->firstOrCreate(
                [
                    "artist_id"  => $objArtist->artist_id,
                    "project_id" => $objProject->project_id
                ],
                [
                    "row_uuid"     => Util::uuid(),
                    "artist_uuid"  => $objArtist->artist_uuid,
                    "project_uuid" => $objProject->project_uuid,
                    "artist_name"  => $objArtist->artist_name,
                    "artist_slug"  => strtolower(trim(preg_replace("/[^A-Za-z0-9-]+/", "-", $objArtist->artist_name))),
                    "project_name" => $objProject->project_name,
                    "project_slug" => strtolower(trim(preg_replace("/[^A-Za-z0-9-]+/", "-", $objProject->project_name))),
                ]
            );
            foreach ($objProject->tracks as $track) {
                $this->mapsTrack->firstOrCreate(
                    [
                        "artist_id"  => $objArtist->artist_id,
                        "project_id" => $objProject->project_id,
                        "track_id"   => $track["track_id"]
                    ],
                    [
                        "row_uuid"     => Util::uuid(),
                        "artist_uuid"  => $objArtist->artist_uuid,
                        "project_uuid" => $objProject->project_uuid,
                        "track_uuid"   => $track->track_uuid,
                        "artist_name"  => $objArtist->artist_name,
                        "artist_slug"  => strtolower(trim(preg_replace("/[^A-Za-z0-9-]+/", "-", $objArtist->artist_name))),
                        "project_name" => $objProject->project_name,
                        "project_slug" => strtolower(trim(preg_replace("/[^A-Za-z0-9-]+/", "-", $objProject->project_name))),
                        "track_name"   => $track->track_name
                    ]
                );
            }
        } catch (Exception $e) {
            info("Error!: " . $e->getMessage() . ". AllMusicInsert: Line - 882");
        }

        return;
    }

    public function getArtistByUrl($strArtistUrl){
        return ($this->artist->where("url_allmusic", $strArtistUrl)->first());
    }

    public function getProjectByUrl($strProjectUrl){
        return ($this->project->where("url_allmusic", $strProjectUrl)->first());
    }
}
