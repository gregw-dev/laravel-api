<?php

namespace App\Services\Music\AllMusic;

use Carbon\Carbon;

class UploadPageToCloud
{
    public function uploadPagesToCloud($objArtist, $objProject, $artistPage, $artistSubPage, $projectPage){
        if ($artistPage) {
            $fileArtistName = str_pad($objArtist->artist_id, 10, "0", STR_PAD_LEFT);
            $path = "content/scraping/allmusic/artist/" . Carbon::now()->format("Y-m-d");
            $pathRelated = "content/scraping/allmusic/artist/" . Carbon::now()->format("Y-m-d");

            $tmpArtistFile = tempnam(null, null);
            file_put_contents($tmpArtistFile, $artistPage);
            bucket_storage("api.music")->putFileAs($path, $tmpArtistFile, $fileArtistName . ".html");

            if ($artistSubPage) {
                $tmpArtistRelatedFile = tempnam(null, null);
                file_put_contents($tmpArtistRelatedFile, $artistSubPage);
                bucket_storage("api.music")->putFileAs($pathRelated, $tmpArtistRelatedFile, $fileArtistName . "-related.html");
            }
        }

        if ($projectPage) {
            $fileProjectName = str_pad($objProject->project_id, 10, "0", STR_PAD_LEFT);
            $path = "content/scraping/allmusic/project/" . Carbon::now()->format("Y-m-d");

            $tmpProjectFile = tempnam(null, null);
            file_put_contents($tmpProjectFile, $projectPage);
            bucket_storage("api.music")->putFileAs($path, $tmpProjectFile, $fileProjectName . ".html");
        }
    }
}
