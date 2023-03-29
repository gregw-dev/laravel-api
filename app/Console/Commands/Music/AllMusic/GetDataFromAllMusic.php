<?php

namespace App\Console\Commands\Music\AllMusic;

use App\Models\Music\Artist\Artist as ArtistModel;
use App\Models\Music\Project\Project as ProjectModel;
use App\Services\Music\AllMusic\AllMusicInsert;
use App\Services\Music\AllMusic\AllMusicScrape;
use App\Services\Music\AllMusic\AllMusicUpdateTracksTables;
use App\Services\Music\AllMusic\UploadPageToCloud;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;
use KubAT\PhpSimple\HtmlDomParser;

class GetDataFromAllMusic extends Command implements ShouldQueue
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "allmusic:scrape";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Command description";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param AllMusicScrape $allMusicScrape
     * @param AllMusicInsert $allMusicInsert
     * @param AllMusicUpdateTracksTables $allMusicUpdateTracksTables
     * @param UploadPageToCloud $uploadPageToCloud
     * @return int
     */
    public function handle(AllMusicScrape $allMusicScrape, AllMusicInsert $allMusicInsert,
                           AllMusicUpdateTracksTables $allMusicUpdateTracksTables, UploadPageToCloud $uploadPageToCloud)
    {
        $genreIds = [
            "MA0000012170",
            "MA0000002467",
            "MA0000002944",
            "MA0000002521",
            "MA0000004433",
            "MA0000002532",
            "MA0000002567",
            "MA0000002572",
            "MA0000002592",
            "MA0000012075",
            "MA0000002660",
            "MA0000002674",
            "MA0000002692",
            "MA0000002745",
            "MA0000002613",
            "MA0000002809",
            "MA0000002816",
            "MA0000002820",
            "MA0000004431",
            "MA0000004432",
            "MA0000011877",
        ];
        $artistModel = resolve(ArtistModel::class);
        $projectModel = resolve(ProjectModel::class);
        $count = 1;
        $pageErrorCheck = 0;
        $allmusicGenre = 0;
        info("Start scrape AllMusic");

        while (true) {
            $objCurl = curl_init();
            curl_setopt_array($objCurl, [
                CURLOPT_URL => "https://www.allmusic.com/advanced-search/results/" . $count,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => "POST",
//                CURLOPT_POSTFIELDS => "filters%5B%5D=releaseyear%3E%3A2018&filters%5B%5D=releaseyear%3C%3A2022&filters%5B%5D=genreid%3A" . $genreIds[$allmusicGenre] . "&sort=",
                CURLOPT_POSTFIELDS => "filters%5B%5D=%26genre%3D" . $genreIds[$allmusicGenre] . "&filters%5B%5D=%26genreBooleanOperator%3DOR&filters%5B%5D=%26releaseYearStart%3Dsy2017&filters%5B%5D=%26releaseYearEnd%3Dey2022&sort=",
                CURLOPT_HTTPHEADER => array(
                    "authority: www.allmusic.com",
                    "accept: text/html, */*; q=0.01",
                    "x-requested-with: XMLHttpRequest",
                    "user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36",
                    "content-type: application/x-www-form-urlencoded; charset=UTF-8",
                    "origin: https://www.allmusic.com",
                    "sec-fetch-site: same-origin",
                    "sec-fetch-mode: cors",
                    "sec-fetch-dest: empty",
                    "referer: https://www.allmusic.com/advanced-search",
                    "accept-language: ru,ru-RU;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6",
                    "cookie: _ga=GA1.2.57901407.1603142426; policy=notified; __qca=P0-863633673-1603142426450; allmusic_session=wtQH6VSE3Km5EJHVNauvzcLipyZq44RsuvLeEfwnYjYt8aoVp%2FyMJ78obwfjP70nqhsDoydGLVvoF7fsAD5zbb6SmoFPON37n5f0NFKAkz3q1XBP47bCTP0oObdoXbJpqfBEtsU7TBtnHamEuMa6ODby2r3TcE9zclH%2Fan9ruGXnZ9YMGpZyX9A1oKof4bz9rAn5gimVmwm2UbcRHDoU2s%2F6xQI0WzJxNSLsVig9bI0OArKNKIum5TdnIxy00aCh7Dl6TwRtmaXgWzq7uHUpLSTYkq63WOoQJChhCCHxA7HXE2XIULyV%2FapnGI%2FDoNmPdHOQuS2mx3fcMBs5WtDpN8OyOzmnbkLz1ma0qTOYGxrxJWo%2FXiseg9Zuj6lm6w8MBKXD47%2Bxbod9pUzK5SFnwYbldMlaW7p49%2BJ3xaIsfuQWj7HRKfCYlj7eQsOAgNmneykwOdoULJpw%2BkYHvBwemQ%3D%3D; _gid=GA1.2.1954103645.1605913477; pb-tracking-id=oinax8idhy8k3q7h; _gat=1; _gat_cToolbarTracker=1; registration_prompt=3; allmusic_session=omgAUQ%2F%2FetPS%2BjL4NjNQzFvmO5ZACPyqECtuOroDbuNerZD8iqXuxZ4h07qPf%2FnCUWXpG3kAL7TuwYXoXOs7xMf5yxNwgqBrAVG1Sh%2FT6FkpzQ1W1dyXUX2l2h1QS245WXk60tBY4sAudLUptNE272BxLmeNfnO0s8%2B%2BrQbwJCUvLYhObWt0ZUsXQp7JRkz7olG4UCUjm9Y%2Ffg91Zrc1mFSUr2QdNXJCNzAOvxwjap76LraZoHMSjxcz%2Bl1LhaJNFXxZQIs2NiBEb7r9roOoKh79yCj0H%2Bp%2BmLGwNumjtWdqB1olkIq46obyemoWyrRCQtgPoBpe1i%2Fcmunt1a09EnL5KqcDi1GXjVKbL%2FZ6VpH1CSK3%2BLWLHDprHFH2YigMj46iCZtwUWx4tVXgzOuT5%2BnNoALRbmRltkEH73qPKWCfqA03gCRrjQe6fGhASegWjqLkhFMVSGHVYsPsYyDW1g%3D%3D"
                ),
            ]);

            $strCurl = curl_exec($objCurl);
            curl_close($objCurl);

            $html = HtmlDomParser::str_get_html($strCurl);

            /* If page returned error */
            if (!$html->find("table")) {
                $pageErrorCheck++;
                Storage::put("test_extracted_page/" . $count . ".html", $strCurl);
                info("Allmusic page ". $count ." returned error.");
                $count++;

                if ($pageErrorCheck == 10) {
                    if ($allmusicGenre == count($genreIds) - 1) {
                        $allmusicGenre = 0;
                    } else {
                        $allmusicGenre ++;
                    }

                    $count = 1;
                }

                continue;
            }

            $pageErrorCheck = 0;
            info("Allmusic script on " . $count . " page.");

            foreach ($html->find("tbody>tr") as $dumpKey => $tr){
                $objArtist = null;
                $objProject = null;
                $strArtistPage = "";
                $strProjectPage = "";
                $strArtistRelatedPage = "";

                if (count($tr->find(".artist>a")) > 1) {
                    $name = "";
                    foreach ($tr->find(".artist>a") as $artist) {
                        $name .= str_replace(" ", "", $artist->plaintext);
                    }

                    $path = "files/" . $name;

                    foreach ($tr->find(".artist>a") as $artistKey => $artist) {
                        Storage::put($path . "/artist" . $artistKey . ".html", $allMusicScrape->getArtistPage($artist->getAttribute("href")));
                    }
                    Storage::put($path . "/project.html", $allMusicScrape->getProjectPage($tr->find(".title>a", 0)->getAttribute("href")));
                } else {
                    try {
                        /* Get Artist and Project Urls */
                        $projectUrl = optional($tr->find(".title>a", 0))->getAttribute("href");
                        $artistUrl = optional($tr->find(".artist>a", 0))->getAttribute("href");

                        $objArtist = $artistModel->where("url_allmusic", $artistUrl)->first();
                        $objProject = $projectModel->where("url_allmusic", $projectUrl)->first();

                        if (empty($objArtist)) {
                            /* Scrape Artist Page */
                            [$arrayArtistParams, $strArtistPage, $strArtistRelatedPage] = $allMusicScrape->scrapeArtistPage($artistUrl);

                            /* Insert Artist Data */
                            if ($arrayArtistParams) {
                                $objArtist = $allMusicInsert->insertArtistToDb($arrayArtistParams);
                            }
                        }

                        if (empty($objProject)) {
                            /* Scrape Project Page */
                            [$arrayProjectParams, $strProjectPage] = $allMusicScrape->scrapeProjectPage($projectUrl, $artistUrl);

                            /* Insert Project Data */
                            if ($arrayProjectParams && $objArtist) {
                                $objProject = $allMusicInsert->insertProjectToDb($arrayProjectParams, $objArtist);
                            }
                        }

                        $allMusicInsert->insertDataToMaps($objArtist, $objProject);

                        $uploadPageToCloud->uploadPagesToCloud($objArtist, $objProject, $strArtistPage, $strArtistRelatedPage, $strProjectPage);
                    } catch (\Exception $e) {
                        info($e->getMessage());
                        info( "Error!: " . $e->getMessage() . ". GetDataFromAllmusic: Line - 188");
                    }
                }
            }

            $count++;
        }

        info("Scrape AllMusic finished.");
        info("Start filling the tables.");

        $allMusicUpdateTracksTables->setupComposers();
        $allMusicUpdateTracksTables->setupFeatures();
        $allMusicUpdateTracksTables->setupPerformers();

        info("Tables have filled.");
        info("Script execution is over successfully.");

        return 0;
    }
}
