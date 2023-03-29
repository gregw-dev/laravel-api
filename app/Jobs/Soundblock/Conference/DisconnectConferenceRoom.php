<?php

namespace App\Jobs\Soundblock\Conference;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Soundblock\Conference;
use Exception;
use App\Models\Soundblock\Accounts\Account;
use App\Contracts\Soundblock\Audit\Bandwidth;
use Illuminate\Support\Facades\Schema;

class DisconnectConferenceRoom
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected  $objRoom;
    protected $arrUuids;

    public function __construct($objRoom,$arrUuids)
    {
        $this->objRoom = $objRoom;
        $this->arrUuids = $arrUuids;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Conference $conferenceService)
    {
        set_time_limit(0);
        //--Check if Conference Room Table Exists
        if (!Schema::hasTable("soundblock_conferences")) {
            return null;
        }
        $this->disconnectRoomEvents($this->objRoom,$this->arrUuids, $conferenceService);
    }

    public function disconnectRoomEvents($objRoom, $arrData,Conference $conferenceService)
    {
        //Update Room Participants Data
        $arrParticipants = $conferenceService->getRoomParticipants($objRoom->sid);
        if (!empty($arrParticipants)) {
            foreach ($arrParticipants as $participant) {
                $strParticipantUuid = $conferenceService->getParticipantUuidFromIdentity($participant["identity"]);
                $objParticipant = $conferenceService->conferenceParticipantRepository->find($strParticipantUuid);
                if ($objParticipant) {
                    $arrParam = ["participating_sid" => $participant["sid"]];
                    $arrParam["room_duration"] = $participant["duration"];
                    if (is_null($objParticipant->room_stop)) {
                        $arrParam["room_stop"] = now();
                    }
                    $conferenceService->updateRoomParticipant($objParticipant, $arrParam);
                }
            }
        }
        //--Retrieve Conference Object
        $objConference = $conferenceService->conferenceRepository->findByRoomSid($objRoom->sid);
        if (!$objConference) {
            throw new Exception("Conference not found while Disconnecting room");
        }
        //-- Update Conference Status
        $conferenceService->conferenceRepository->update($objConference, [
            "room_stop" => now(),
            "room_duration" => $objRoom->duration
        ]);
        //-- Get Room Recordings
        $arrRoomRecordings = $conferenceService->getRoomRecordings($objRoom->sid);
        if (!empty($arrRoomRecordings)) {
            foreach ($arrRoomRecordings as $arrRecord) {
                $objParticipant = $conferenceService->conferenceParticipantRepository->findByParticipantSid($arrRecord["participating_sid"]);
                if ($objParticipant && $objParticipant->user_id !== null) {
                    $objUser = $conferenceService->userService->find($objParticipant->user_id);
                } else {
                    $objUser = (object) [];
                }
                if (isset($arrData["account_uuid"])) {
                    $objUuid = (object) [
                        "account_uuid" => $arrData["account_uuid"],
                        "project_uuid" => null,
                        "type" => "account"
                    ];
                } else {
                    $objUuid = (object) [
                        "account_uuid" => null,
                        "project_uuid" => $arrData["project_uuid"],
                        "type" => "project"
                    ];
                }

                $this->processRecording($arrRecord, $objConference, $objUser, $objUuid,$conferenceService);

            }
        }else{
            return ("Room recording not yet Retrieved");
        }
    }

    public function processRecording($arrRecording, $objConference, $objUser, $objUuid,Conference $conferenceService)
    {
        $strLocalFilePath = $conferenceService->getMediaContent($arrRecording["media_location"]);
        $fileAdapter =bucket_storage("soundblock");
        if(is_null($strLocalFilePath)){
             return "no_file_found";

        }

        $objParticipant = $conferenceService->conferenceParticipantRepository->findByParticipantSid($arrRecording["participating_sid"]);
        if($objUuid->type=="project"){
        $objProject = $conferenceService->getProject($objUuid->project_uuid ?? $objUuid->account_uuid, $objUuid->type);
        }
        if($objUuid->type=="account") {

            $objAccount = Account::where("account_uuid", $objUuid->account_uuid)->first();
        }

        if (!$objParticipant) {
            throw new Exception("Participant not found while processing Recorded Files");
        }
        //--Check if Participant Record Has been Processed Before Record
        if ($arrRecording["media_type"] == "video" && !empty($objParticipant->media_video_url)) {
            return ("already_processed_video_file");
        }

        if ($arrRecording["media_type"] == "audio" && !empty($objParticipant->media_audio_url)) {
            // return "already_processed";
            return ("already_processed_audeo_file");
        }

        $strFileExtension = last(explode(".", $strLocalFilePath));
        //-- Upload to Core S3 Bucket
        if ($objUuid->type == "account") {
            $strFilePath = "accounts/{$objUuid->account_uuid}/conference";
        } else {
            $strFilePath = "accounts/{$objProject->account_uuid}/projects/{$objProject->project_uuid}/conference";
        }

        $strFileName = "{$objConference->conference_uuid}_$objParticipant->participant_uuid.{$strFileExtension}";

        if (file_exists($strLocalFilePath)) {
            $strPermissionName = $conferenceService->generatePermissionName($objUuid->type, "record");
            $strUuid = $objUuid->type == "project" ? $objUuid->project_uuid : $objUuid->account_uuid;
            $strPermissionGroup = $conferenceService->generatePermissionGroupName($objUuid->type, $strUuid);
            $blnRecordPermission = is_authorized($objUser, $strPermissionGroup, $strPermissionName);
            if ($blnRecordPermission) {
                $blnUploaded =   $fileAdapter->putFileAs("public/" . $strFilePath, $strLocalFilePath, $strFileName, ["visibility" => "public"]);
                if (!$blnUploaded) {
                    throw new Exception("File Upload Error : {$strLocalFilePath} ");
                }
                unlink($strLocalFilePath);
                // echo cloud_url("soundblock") . "$strFilePath/$strFileName ";
            } else {
                $blnUploaded = false;
            }

            $conferenceService->twilioClient->video->v1->recordings($arrRecording["recording_sid"])->delete();

            if ($blnUploaded) {


                $objUser = $conferenceService->userService->find($objParticipant->user_id);
                if ($objParticipant) {
                    $arrUpdateData = [
                        "participant_diskspace" => $arrRecording["media_size"] + $objParticipant->participant_diskspace,
                        "participant_bandwidth" => $arrRecording["media_size"] + $objParticipant->participant_bandwidth,
                        "participating_sid" => $arrRecording["participating_sid"],
                        "stamp_updated" => time(),
                        "stamp_updated_at" => now(),
                        "stamp_updated_by" => 1
                    ];
                    if ($blnRecordPermission) {
                        $arrUpdateData["flag_record"] = true;
                    }
                    if ($arrRecording["media_type"] == "video") {
                        $arrUpdateData = array_merge($arrUpdateData, [
                            "recording_video_sid" => $arrRecording["recording_sid"],
                            "media_video_url" => $arrRecording["media_url"],
                            "media_video_location" => $arrRecording["media_location"]
                        ]);
                    } else {
                        $arrUpdateData = array_merge($arrUpdateData, [
                            "recording_audio_sid" => $arrRecording["recording_sid"],
                            "media_audio_url" => $arrRecording["media_url"],
                            "media_audio_location" => $arrRecording["media_location"]
                        ]);
                    }
                    $conferenceService->updateRoomParticipant($objParticipant, $arrUpdateData);
                    if ($objUuid->type == "project" && $objProject) {
                        //--Update Bandwidth services
                        $conferenceService->bandwidthService->create($objProject, $objUser, $arrRecording["media_size"], Bandwidth::CONFERENCE);
                        //-- Update Diskspace Services
                        if ($blnRecordPermission) {
                            $conferenceService->diskSpaceService->save($objProject, $arrRecording["media_size"]);
                        }
                    }
                    if ($objUuid->type == "account" && $objAccount) {
                        //--Update Bandwidth Service
                        $conferenceService->bandwidthService->storeAccountBandwidth($objAccount,  $objUser, $arrRecording["media_size"], Bandwidth::CONFERENCE);
                        //-- Update Diskspace Service
                        if ($blnRecordPermission) {
                            $conferenceService->diskSpaceService->saveAccountDiskspace($objAccount, $arrRecording["media_size"]);
                        }
                    }
                }
            }
        } else {
             return ("File does not Exist {$strLocalFilePath}");
        }
        return true;
    }

}
