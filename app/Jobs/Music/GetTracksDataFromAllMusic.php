<?php

namespace App\Jobs\Music;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use KubAT\PhpSimple\HtmlDomParser;
use App\Services\Music\AllMusic\AllMusicInsertTracksMetadata;

class GetTracksDataFromAllMusic implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /** @var array */
    private array $arrayTracks;

    /**
     * Create a new job instance.
     *
     * @param array $arrayTracks
     */
    public function __construct(array $arrayTracks)
    {
        $this->arrayTracks = $arrayTracks;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!empty($this->arrayTracks)) {
            foreach ($this->arrayTracks as $objTrack) {
                $trackUrl = optional($objTrack)->url_allmusic;
                $trackGenres = optional($objTrack)->genres;
                $trackMoods = optional($objTrack)->moods;
                $trackStyles = optional($objTrack)->styles;
                $trackThemes = optional($objTrack)->themes;

                if (!empty($trackUrl) && (empty($trackGenres) || empty($trackMoods) || empty($trackStyles) || empty($trackThemes))) {
                    sleep(4);
                    $tracksData = [];
                    $objCurl = curl_init();
                    curl_setopt_array($objCurl, [
                        CURLOPT_URL => $trackUrl,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER => array(
                            "user-agent: Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)",
                        ),
                    ]);

                    $strTrackCurl = curl_exec($objCurl);
                    curl_close($objCurl);

                    $trackHtml = HtmlDomParser::str_get_html($strTrackCurl);

                    if (!is_bool($trackHtml)) {
                        $strMatch = "/(.*?)\(/s";

                        /* Get Genres for Track */
                        foreach ($trackHtml->find("div.song_genres>div.middle>a") as $key => $genre) {
                            preg_match($strMatch, $genre->plaintext, $matches);
                            $tracksData["genres"][$key] = trim($matches[1]);
                        }

                        /* Get Styles for Track */
                        foreach ($trackHtml->find("div.song_styles>div.middle>a") as $key => $style) {
                            preg_match($strMatch, $style->plaintext, $matches);
                            $tracksData["styles"][$key] = trim($matches[1]);
                        }

                        /* Get Moods for Track */
                        foreach ($trackHtml->find("div.song_moods>div.middle>a") as $key => $mood) {
                            preg_match($strMatch, $mood->plaintext, $matches);
                            $tracksData["moods"][$key] = trim($matches[1]);
                        }

                        /* Get Themes for Track */
                        foreach ($trackHtml->find("div.song_themes>div.middle>a") as $key => $theme) {
                            preg_match($strMatch, $theme->plaintext, $matches);
                            $tracksData["themes"][$key] = trim($matches[1]);
                        }

                        if (!empty($tracksData)) {
                            AllMusicInsertTracksMetadata::insertMetaData($tracksData, $objTrack);
                        }
                    }
                }
            }
        }
    }
}
