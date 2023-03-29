<?php

namespace App\Helpers\Filesystem;

use App\Helpers\Util;
use App\Models\Soundblock\Collections\Collection;
use App\Models\Soundblock\Files\File;
use App\Models\Soundblock\Projects\Project;
use App\Models\Users\User;
use App\Models\Soundblock\Tracks\Track;
use Illuminate\Support\Facades\Auth;
use App\Models\Soundblock\Artist as ArtistModel;

abstract class Soundblock extends Filesystem {
    public static function default_project_artwork_path(): string {
        return "images/artwork.png";
    }

    public static function project_path(Project $objProject): string {
        return "accounts/{$objProject->account_uuid}/projects/{$objProject->project_uuid}/";
    }

    public static function project_files_path(Project $objProject): string {
        return self::project_path($objProject) . "files/";
    }

    public static function zip_project_artwork_path(Project $objProject): string {
        return "public/" . self::project_path($objProject) . "artwork.png";
    }

    public static function project_artwork_path(Project $objProject, string $code): string {
        return self::project_path($objProject) . "artwork.png?v=" . $code;
    }

    public static function upload_project_artwork_path(Project $objProject, string $code): string {
        return "public/" . self::project_path($objProject) . "artwork.png?v=" . $code;
    }

    public static function move_project_artwork_path(Project $objProject): string {
        return "public/" . self::project_path($objProject) . "artwork.png";
    }

    public static function project_file_path(Project $objProject, File $objFile): string {
        $strExtension = Util::file_extension($objFile->file_name);

        return self::project_files_path($objProject) . $objFile->file_uuid . "." . $strExtension;
    }

    public static function project_track_artwork(Project $objProject, File $objFile): string {
        return  self::project_files_path($objProject) . "{$objFile->file_uuid}.png";
    }

    public static function upload_path(?string $fileName = null): string {
        $strPath = "public/uploads";

        if (is_null($fileName)) {
            return $strPath;
        }

        return $strPath . "/{$fileName}";
    }

    public static function unzip_path(?string $dirName = null): string {
        return self::upload_path() . "/" . is_null($dirName) ? Util::uuid() : $dirName;
    }

    public static function download_path() {
        return "download";
    }

    public static function download_zip_path(string $zipName, ?User $objUser = null) {
        if (is_object($objUser)) {
            return self::download_path() . "/{$objUser->user_uuid}/{$zipName}.zip";
        }

        if (Auth::check()) {
            $objUser = Auth::user();

            return  self::download_path() . "/{$objUser->user_uuid}/{$zipName}.zip";
        }

        return  self::download_path() . "/{$zipName}.zip";
    }

    public static function deployment_project_path(Project $objProject) {
        return self::project_path($objProject) . "deployments";
    }

    public static function deployment_project_zip_path_old(Collection $objCollection) {
        $fileName = "Music Deployment - " . Filesystem::urlTitle($objCollection->project->project_title) .
            " - " . Filesystem::urlTitle($objCollection->project->project_artist) .
            " - " . $objCollection->collection_uuid;

        return self::deployment_project_path($objCollection->project) . "/" . $fileName . ".zip";
    }

    public static function deployment_project_zip_path(Collection $objCollection) {
        $fileName = "Music Deployment - " . $objCollection->collection_uuid;

        return self::deployment_project_path($objCollection->project) . "/" . $fileName . ".zip";
    }

    public static function deployment_project_track_path(Track $objTrack) {
        $objCollection = $objTrack->collections()->first();
        $strTrackNumberPrefix = str_pad($objTrack->track_number, 3, 0, STR_PAD_LEFT);
        $strTrackVolumeNumberPrefix = str_pad($objTrack->track_volume_number, 2, 0, STR_PAD_LEFT);
        $fileName =
            $strTrackVolumeNumberPrefix .
            " - " . $strTrackNumberPrefix .
            " - " . "Music Deployment" .
            " - " . Filesystem::urlTitle($objCollection->project->project_title) .
            " - " . Filesystem::urlTitle($objCollection->project->project_artist) .
            " - " . Filesystem::urlTitle($objTrack->file->file_title) .
            " - " . $objTrack->file_uuid
        ;

        return "soundblock" . "/" . self::deployment_project_path($objCollection->project) . "/" . $fileName . ".wav";
    }

    public static function download_deployment_project_track_path(Track $objTrack) {
        $objCollection = $objTrack->collections()->first();
        $strTrackNumberPrefix = str_pad($objTrack->track_number, 3, 0, STR_PAD_LEFT);
        $strTrackVolumeNumberPrefix = str_pad($objTrack->track_volume_number, 2, 0, STR_PAD_LEFT);
        $fileName =
            $strTrackVolumeNumberPrefix .
            " - " . $strTrackNumberPrefix .
            " - " . "Music Deployment" .
            " - " . Filesystem::urlTitle($objCollection->project->project_title) .
            " - " . Filesystem::urlTitle($objCollection->project->project_artist) .
            " - " . Filesystem::urlTitle($objTrack->file->file_title) .
            " - " . $objTrack->file_uuid
        ;

        return "soundblock" . "/" . self::deployment_project_path($objCollection->project) . "/" . $fileName . ".wav";
    }

    public static function office_deployment_project_zip_path_old(Collection $objCollection) {
        $fileName = "Music Deployment - " . Filesystem::urlTitle($objCollection->project->project_title) .
            " - " . Filesystem::urlTitle($objCollection->project->project_artist) .
            " - " . $objCollection->collection_uuid;

        return "soundblock" . "/" . self::deployment_project_path($objCollection->project) . "/" . $fileName . ".zip";
    }

    public static function office_deployment_project_zip_path(Collection $objCollection) {
        $fileName = "Music Deployment - " . $objCollection->collection_uuid;

        return "soundblock" . "/" . self::deployment_project_path($objCollection->project) . "/" . $fileName . ".zip";
    }

    public static function artists_avatar_full_path(ArtistModel $objArtist){
        $avatarName = $objArtist->artist_uuid . ".png";

        return "accounts/{$objArtist->account_uuid}/artists/" . $avatarName;
    }

    public static function artists_avatar_path(ArtistModel $objArtist){
        return "accounts/{$objArtist->account_uuid}/artists/";
    }

    public static function artists_draft_avatar_path(ArtistModel $objArtist){
        $avatarName = $objArtist->artist_uuid . ".png";

        return "upload" . "/" . "artists" . "/" . $avatarName;
    }

    public static function user_w9_form_path(User $objUser){
        return ("private/users/" . $objUser->user_uuid);
    }

    public static function full_user_w9_form_path(User $objUser){
        return (self::user_w9_form_path($objUser) . "/w9.pdf");
    }

    public static function apple_reports_path(string $strType){
        return ("reports/Apple/" . $strType);
    }

    public static function apple_reports_file_path(string $strType, string $strFileName){
        return (self::apple_reports_path($strType) . "/" . $strFileName);
    }
}
