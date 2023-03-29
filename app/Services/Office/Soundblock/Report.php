<?php

namespace App\Services\Office\Soundblock;

use File;
use Util;
use App\Helpers\Filesystem\Soundblock as SoundblockHelper;
use App\Jobs\Soundblock\Reports\Apple\ProcessAppleReport as ProcessAppleReportJob;
use App\Models\BaseModel;
use App\Models\Users\User as UserModel;
use App\Models\Soundblock\Reports\Music as ProcessedMusicFiles;
use App\Repositories\Soundblock\Platform as PlatformRepository;
use App\Repositories\Soundblock\Data\PlatformReportMetadata as PlatformReportMetadataRepository;
use App\Support\Soundblock\MusicAppleReports as MusicAppleReportsSupport;

class Report {
    /** @var PlatformReportMetadataRepository */
    private PlatformReportMetadataRepository $platformReportMetadataRepo;
    /** @var PlatformRepository */
    private PlatformRepository $platformRepo;
    /** @var MusicAppleReportsSupport */
    private MusicAppleReportsSupport $musicAppleReportsSupport;

    /**
     * @param PlatformReportMetadataRepository $platformReportMetadataRepo
     * @param PlatformRepository $platformRepo
     * @param MusicAppleReportsSupport $musicAppleReportsSupport
     */
    public function __construct(PlatformReportMetadataRepository $platformReportMetadataRepo, PlatformRepository $platformRepo,
                                MusicAppleReportsSupport $musicAppleReportsSupport) {
        $this->platformRepo = $platformRepo;
        $this->platformReportMetadataRepo = $platformReportMetadataRepo;
        $this->musicAppleReportsSupport = $musicAppleReportsSupport;
    }

    public function getReports(string $strPlatform = null, string $strDate = null, string $strStatus = null, int $intPerPage = 10){
        $objApplePlatform = $this->platformRepo->findByName("Apple Music");
        $objRecords = $this->platformReportMetadataRepo->findByPlatformDateAndStatus($strPlatform, $strDate, $strStatus, $intPerPage);

        foreach ($objRecords as $key => $objRecord) {
            if ($objRecord->platform_id == $objApplePlatform->platform_id) {
                $strCloudUrl = cloud_url("soundblock") . str_replace(" ", "%20", SoundblockHelper::apple_reports_file_path($objRecord->report_type, $objRecord->report_file_name));
                $objRecords[$key]["download_report_url"] = $strCloudUrl;
            }
        }

        return ($objRecords);
    }

    public function processAppleReports(UserModel $objUser, array $arrFiles){
        foreach ($arrFiles as $file) {
            if ($file->extension() == "zip") {
                $strFileNameHashed =  md5($file->getClientOriginalName());
                $strLocalFilePath = storage_path("app/reports/{$strFileNameHashed}.zip");
                $strDirectoryPath = storage_path("app/reports/{$strFileNameHashed}");

                File::put($strLocalFilePath, $file->getContent());

                $zip = new \ZipArchive();
                $isOpened = $zip->open($strLocalFilePath);

                if ($isOpened) {
                    $zip->extractTo($strDirectoryPath);
                } else {
                    throw new \Exception("Unable to open archive {$strLocalFilePath}");
                }

                $zip->close();

                File::delete($strLocalFilePath);
                $arrFiles = File::files($strDirectoryPath);

                foreach ($arrFiles as $objUnzippedFile) {
                    try {
                        $strFileName = $objUnzippedFile->getFilename();

                        if ($this->platformReportMetadataRepo->canProcessFile($strFileName) && !ProcessedMusicFiles::where("file_path", $strFileName)->exists()){
                            $this->uploadAndProcessFile($objUser, $file);
                        }
                    } catch (\Exception $exception) {
                        File::delete($objUnzippedFile);
                        throw new \Exception($exception->getMessage());
                    }
                }

                File::deleteDirectory($strDirectoryPath);
            } else {
                $this->uploadAndProcessFile($objUser, $file);
            }
        }

        return (true);
    }

    private function uploadAndProcessFile(UserModel $objUser, $objFile){
        $soundblockAdapter = bucket_storage("soundblock");
        $objApplePlatform = $this->platformRepo->findByName("Apple Music");

        $strFileName = $objFile->getClientOriginalName();
        $strReportType = $this->musicAppleReportsSupport->defineReportType($strFileName);

        $objReportMeta = $this->platformReportMetadataRepo->create([
            "data_uuid" => Util::uuid(),
            "platform_id" => $objApplePlatform->platform_id,
            "platform_uuid" => $objApplePlatform->platform_uuid,
            "report_type" => $strReportType,
            "report_file_name" => $strFileName,
            "status" => "Processing",
            BaseModel::STAMP_CREATED_BY => $objUser->user_id,
            BaseModel::STAMP_UPDATED_BY => $objUser->user_id
        ]);

        $strFilePath = $soundblockAdapter->putFileAs(
            SoundblockHelper::apple_reports_path($strReportType),
            $objFile,
            $strFileName,
            ["visibility" => "public"]
        );

        if ($strFilePath) {
            dispatch(new ProcessAppleReportJob($strFilePath, $strFileName, $objReportMeta));
        }
    }
}
