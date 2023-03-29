<?php

namespace App\Services\Music\AllMusic;

use Str;
//use App\Models\Music\Genre;
//use App\Models\Music\Mood;
//use App\Models\Music\Style;
//use App\Models\Music\Theme;

class AllMusicInsertTracksMetadata
{
    /**
     * AllMusicInsertTracksMetadata constructor.
     */
    public function __construct(){

    }

    public static function insertMetaData(array $tracksMeta, $objTrack){
        if (!empty($tracksMeta["genres"])) {
            foreach ($tracksMeta["genres"] as $genre){
//                $objGenre = Genre::where("genre", $genre)->first();
                if (!empty($objGenre)) {
                    $objTrack->genres()->attach($objTrack->track_id, [
                        "row_uuid" => strtoupper(Str::uuid()),
                        "track_uuid" => $objTrack->track_uuid,
                        "track_genre" => $genre,
//                        "genre_id" => $objGenre->genre_id,
//                        "genre_uuid" => $objGenre->genre_uuid,
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s")
                    ]);
                }
            }
        }
        if (!empty($tracksMeta["moods"])) {
            foreach ($tracksMeta["moods"] as $mood){
//                $objMood = Mood::where("mood", $mood)->first();
                if (!empty($objMood)) {
                    $objTrack->moods()->attach($objTrack->track_id, [
                        "row_uuid" => strtoupper(Str::uuid()),
                        "track_uuid" => $objTrack->track_uuid,
                        "track_mood" => $mood,
//                        "mood_id" => $objMood->mood_id,
//                        "mood_uuid" => $objMood->mood_uuid,
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s")
                    ]);
                }
            }
        }
        if (!empty($tracksMeta["styles"])) {
            foreach ($tracksMeta["styles"] as $style){
//                $objStyle = Style::where("style_name", $style)->first();
                if (!empty($objStyle)) {
                    $objTrack->styles()->attach($objTrack->track_id, [
                        "row_uuid" => strtoupper(Str::uuid()),
                        "track_uuid" => $objTrack->track_uuid,
                        "track_style" => $style,
//                        "style_id" => $objStyle->style_id,
//                        "style_uuid" => $objStyle->style_uuid,
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s")
                    ]);
                }
            }
        }
        if (!empty($tracksMeta["themes"])) {
            foreach ($tracksMeta["themes"] as $theme){
//                $objTheme = Theme::where("theme_name", $theme)->first();
                if (!empty($objTheme)) {
                    $objTrack->themes()->attach($objTrack->track_id, [
                        "row_uuid" => strtoupper(Str::uuid()),
                        "track_uuid" => $objTrack->track_uuid,
                        "track_theme" => $theme,
//                        "theme_id" => $objTheme->theme_id,
//                        "theme_uuid" => $objTheme->theme_uuid,
                        "stamp_epoch" => time(),
                        "stamp_date" => date("Y-m-d"),
                        "stamp_time" => date("G:i:s")
                    ]);
                }
            }
        }
    }
}
