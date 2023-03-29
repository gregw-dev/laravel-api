<?php


namespace App\Services\Soundblock;


use App\Helpers\Filesystem\Soundblock;
use App\Repositories\Core\Auth\AuthGroup;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VideoGrant;
use Illuminate\Support\Facades\Auth;
use Twilio\Rest\Client;
use App\Helpers\Client as ClientHelper;
use App\Helpers\Util;
use App\Repositories\Soundblock\Conference as ConferenceRepository;
use App\Repositories\Soundblock\ConferenceParticipant as ConferenceParticipantRepository;
use Exception;
use App\Contracts\Soundblock\Audit\Bandwidth;
use App\Contracts\Soundblock\Audit\Diskspace;
use App\Services\User;
use App\Jobs\Soundblock\Conference\DisconnectConferenceRoom;
use App\Events\Common\PrivateNotification;
use App\Models\Soundblock\Accounts\Account;

class Conference
{
    public $sid;
    public $token;
    public $key;
    public $secret;
    public AuthGroup $groupRepo;
    public ConferenceRepository $conferenceRepository;
    public ConferenceParticipantRepository $conferenceParticipantRepository;
    public Client $twilioClient;
    public Bandwidth $bandwidthService;
    public Diskspace $diskSpaceService;
    public User $userService;
    public Project $projectService;

    const CONNECTED_PARTICIPANTS = ["status" => "connected"];
    const DISCONNECTED_PARTICIPANTS = ["status" => "disconnected"];

    public function __construct(AuthGroup $groupRepo, ConferenceRepository $conferenceRepository, ConferenceParticipantRepository $conferenceParticipantRepository, Bandwidth $bandwidthService, User $userService, Project $projectService, Diskspace $diskSpaceService)
    {
        $this->sid = config("services.twilio.sid");
        $this->token = config("services.twilio.token");
        $this->key = config("services.twilio.key");
        $this->secret = config("services.twilio.secret");
        $this->groupRepo = $groupRepo;
        $this->conferenceRepository = $conferenceRepository;
        $this->conferenceParticipantRepository = $conferenceParticipantRepository;
        $this->twilioClient = new Client($this->sid, $this->token);
        $this->bandwidthService = $bandwidthService;
        $this->userService = $userService;
        $this->projectService = $projectService;
        $this->diskSpaceService = $diskSpaceService;
    }

    public function generateToken($roomName, $objParticipant,$objUser=null)
    {
        $objUser = $objUser ?? auth()->user();
        $identity = json_encode([
            "participant_uuid" => $objParticipant->participant_uuid,
            "participant_name" => $objUser->name
        ]);
        $token = new AccessToken($this->sid, $this->key, $this->secret, 3600, $identity);
        $videoGrant = new VideoGrant();
        $videoGrant->setRoom($roomName);
        $token->addGrant($videoGrant);
        return $token->toJWT();
    }

    /**
     * generate room name from data array
     * @param $data
     * @return string
     */
    public function generateRoomName($data)
    {
        if (!empty($data["account_uuid"])) {
            $strUniqueRoomName = "Soundblock.Account." . $data["account_uuid"];
        } else {
            $strUniqueRoomName = "Soundblock.Project." . $data["project_uuid"];
        }
        return $strUniqueRoomName;
    }

    public function generatePermissionName($strType = "account", $strAction = "join"): String
    {
        $strAction = ucfirst($strAction);
        $strType = ucfirst($strType);
        return "App.Soundblock.{$strType}.Conference.{$strAction}";
    }

    public function generatePermissionGroupName($type, $uuid): String
    {
        $type = ucfirst($type);
        return "App.Soundblock.{$type}.$uuid";
    }

    /**
     * Based on "account_uuid" or "project_uuid" a room will be created,
     * if the room already exists, it will just return the access token for that room
     * @param $data
     * @return array
     * @throws \Twilio\Exceptions\ConfigurationException
     * @throws \Twilio\Exceptions\TwilioException
     */
    public function joinOrCreateRoom($data)
    {
        $strUniqueRoomName = $this->generateRoomName($data);
        try {
            $objRoom = $this->twilioClient->video->v1->rooms($strUniqueRoomName)->fetch();
            $objConference = $this->conferenceRepository->findByRoomSid($objRoom->sid);
            if ($objConference) {
                $objParticipant = $this->createNewParticipant($objConference);
            } else {
                return "an_error_occured_room_exisiting_without_conference_record_on_db";
            }
        } catch (\Throwable $th) {

            //creating new room
            $objRoom = $this->twilioClient->video->v1->rooms->create([
                "uniqueName" => $strUniqueRoomName,
                "type" => "group",
                "recordParticipantsOnConnect" => True //records video
            ]);

            $objConference = $this->conferenceRepository->create([
                "conference_uuid" => Util::uuid(),
                "room_sid" => $objRoom->sid,
                "room_name" => $objRoom->uniqueName,
                "room_start" => now()
            ]);

            $objParticipant = $this->createNewParticipant($objConference);
        }
        // Update Participant table with participating_sid
        $arrParticipants = $this->getRoomParticipants($objRoom->sid);
        if (!empty($arrParticipants)) {
            foreach ($arrParticipants as $participant) {
                $strParticipantUuid = $this->getParticipantUuidFromIdentity($participant["identity"]);
                $objParticipant = $this->conferenceParticipantRepository->find($strParticipantUuid);
                if ($objParticipant) {
                    $this->updateRoomParticipant($objParticipant, ["participating_sid" => $participant["sid"]]);
                }
            }
        }
        $objUser = auth()->user();
        return [
            "accessToken" => $this->generateToken($objRoom->uniqueName, $objParticipant),
            "status" => $objRoom->status,
            "room_sid" => $objRoom->sid,
            "roomName" => $objRoom->uniqueName,
            "max_participants" => $objRoom->maxParticipants,
            "participant_uuid" => $objParticipant->participant_uuid,
            "participant_name" => $objUser->name
        ];
    }

    public function createNewParticipant($objConference)
    {
        $objUser = Auth::user();
        return  $this->conferenceParticipantRepository->create([
            "participant_uuid" => Util::uuid(),
            "conference_id" => $objConference->conference_id,
            "conference_uuid" => $objConference->conference_uuid,
            "user_id" => $objUser->user_id,
            "user_uuid" => $objUser->user_uuid,
            "participating_sid" => "",
            "participant_ping" => now(),
            "room_start" => now(),
            "stamp_created" => time(),
            "stamp_created_at" => now(),
            "stamp_created_by" => $objUser->user_id,
            "stamp_updated" => time(),
            "stamp_updated_at" => now(),
            "stamp_updated_by" => $objUser->user_id,
        ]);
    }

    /**
     * Returning the room data in a ready state to send to front end
     * @param $uuid
     * @param $room_type
     * @param $arrInProgressRooms
     * @return string[]
     */
    public function getFormattedRoomData($obj, $room_type, $arrInProgressRooms)
    {
        $status = "completed";

        if ($room_type === "account") {
            $strUniqueRoomName = "Soundblock.Account." . $obj->account_uuid;
            $roomTitle = $obj->account_name;
            $artwork = "";
            $resp = [
                "account_uuid" => $obj->account_uuid,
                "room_name"    => $strUniqueRoomName,
                "room_title"   => $roomTitle,
                "status"       => $status,
                "artwork"      => $artwork
            ];
        } else {
            $strUniqueRoomName = "Soundblock.Project." . $obj->project_uuid;
            $roomTitle = $obj->project_title;
            $artwork = cloud_url("soundblock") . Soundblock::project_artwork_path($obj, $obj->project_artwork_rand);
            $resp = [
                "account_uuid" => $obj->account_uuid,
                "room_name"    => $strUniqueRoomName,
                "room_title"   => $roomTitle,
                "status"       => $status,
                "artwork"      => $artwork
            ];
        }

        if ($arrInProgressRooms->contains($strUniqueRoomName)) {
            $resp["status"] = "in-progress";
        } else {
            $resp["status"] = "completed";
        }

        return $resp;
    }

    /**
     * will return the array of authorized room
     * @return array
     */
    public function getAuthorizedRoomList()
    {
        $objUser = $this->userService->find(Auth::user()->user_id);
        //        $arrAccountUuid = Auth::user()->userAccounts->pluck("account_uuid")->toArray();
        $arrProjectGroup = $this->groupRepo->getLikelyByUser($objUser, "App.Soundblock.Project.%")->pluck("group_name")->toArray();
        $arrProjectUuids = array_map([Util::class, "uuid"], $arrProjectGroup);
        $arrAccountList = $objUser->accounts()->with("projects", function ($query) use ($arrProjectUuids) {
            $query->whereIn("soundblock_projects.project_uuid", $arrProjectUuids);
        })->get();
        $arrInProgressRooms = $this->getInProgressRoomList();
        $respArr = [];
        foreach ($arrAccountList as $k => $v) {
            //            $arrAccountRoom = (object)[];
            //            if(in_array($v->account_uuid,$arrAccountUuid)) {
            $arrAccountRoom = $this->getFormattedRoomData($v, "account", $arrInProgressRooms);
            //            }

            $arrProjectRooms = [];
            foreach ($v->projects as $vk => $vv) {
                $arrProjectRooms[] = $this->getFormattedRoomData($vv, "project", $arrInProgressRooms);
            }
            $arrAccountRoom["projects"] = $arrProjectRooms;
            $respArr[] =  $arrAccountRoom;
        }
        return ["rooms" => $respArr];
    }

    public function getInProgressRoomList(): \Illuminate\Support\Collection
    {
        $twilio = new Client($this->sid, $this->token);

        //Fetching room list from twilio
        $arrInProgressRooms = $twilio->video->rooms->read(
            [
                //                "type" => "group",
                "status" => "in-progress",
            ]
        );
        return collect($arrInProgressRooms)->pluck("uniqueName");
    }

    public function getRoomParticipants($strRoomSid, $type = null)
    {
        try {

            $arrParticipants = is_null($type)
                ? $this->twilioClient->video->v1->rooms($strRoomSid)->participants->read()
                : $this->twilioClient->video->v1->rooms($strRoomSid)->participants->read($type);
        } catch (\Throwable $th) {
            throw new Exception("Room Participants not retrieved Successfully");
        }

        $arrOutput = [];
        foreach ($arrParticipants as $participant) {
            $arrItemData = [
                "sid" => $participant->sid,
                "room_sid" => $participant->roomSid,
                "account_sid" => $participant->accountSid,
                "duration" => $participant->duration,
                "identity" => $participant->identity,
                "url" => $participant->url,
                "links" => $participant->links,
                "status" => $participant->status,
                "start_time" => $participant->startTime,
                "end_time" => $participant->endTime,
                "date_created" => $participant->dateCreated,
                "date_updated" => $participant->dateUpdated,
            ];
            array_push($arrOutput, $arrItemData);
        }
        return $arrOutput;
    }

    public function getRoomParticipant($strRoomSid, $strParticipatingSid)
    {
        try {
            $objParticipant = $this->twilioClient->video->v1->rooms($strRoomSid)->participants($strParticipatingSid)->fetch();
        } catch (\Throwable $th) {
            throw new Exception("Participant Not Found");
        }

        return  [
            "sid" => $objParticipant->sid,
            "room_sid" => $objParticipant->roomSid,
            "account_sid" => $objParticipant->accountSid,
            "duration" => $objParticipant->duration,
            "identity" => $objParticipant->identity,
            "url" => $objParticipant->url,
            "links" => $objParticipant->links,
            "status" => $objParticipant->status,
            "start_time" => $objParticipant->startTime,
            "end_time" => $objParticipant->endTime,
            "date_created" => $objParticipant->dateCreated,
            "date_updated" => $objParticipant->dateUpdated,
        ];
    }

    public function disconnectRoomParticipant($strRoomSid, $strParticipatingSid)
    {
        try {
            $this->twilioClient->video->v1->rooms($strRoomSid)->participants($strParticipatingSid)->update(["status" => "disconnected"]);
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
          }
        return $strParticipatingSid;
    }

    public function disconnectRoom($arrData)
    {
        //--Get Participant Data
        $objConferenceParticipant = $this->getUserActiveConferenceRoom();
        $objConference = $objConferenceParticipant->conference;
        //-- Disconnect Participant
        $this->disconnectRoomParticipant($objConference->room_sid,$objConferenceParticipant->participating_sid);
        //-- Count Remaining participants
        $getRoomParticipants = $this->getRoomParticipants($objConference->room_sid, Conference::CONNECTED_PARTICIPANTS);
        if (count($getRoomParticipants) > 0) {

            return $objConference->room_sid;
        }
        $strUniqueRoomName = $this->generateRoomName($arrData);
        try {
            $objRoom = $this->twilioClient->video->v1->rooms($strUniqueRoomName)->fetch();
            $this->twilioClient->video->v1->rooms($objRoom->sid)->update("completed");
            //-- Update ObjRoom object
            $objRoom = $this->twilioClient->video->v1->rooms($objRoom->sid)->fetch();

        } catch (\Throwable $th) {
            throw new Exception("Room Does not Exist");
        }

        disconnectConferenceRoom::dispatch($objRoom,$arrData);
//         $this->disconnectRoomEvents($objRoom, $arrData);
        return $objRoom->sid;
    }

    public function getConferenceRoom($strRoomSid)
    {
        try {
            return $this->twilioClient->video->v1->rooms($strRoomSid)->fetch();
        } catch (\Throwable $th) {
            throw new Exception("Room Not Found");
        }
    }

    public function saveRoomParticipants($strRoomSid)
    {
        $arrParticipants = $this->getRoomParticipants($strRoomSid);
        $objUser = Auth::user();
        foreach ($arrParticipants as $participant) {
            if (!$this->conferenceParticipantRepository->findByParticipantSid($participant->sid)) {
                $this->conferenceParticipantRepository->create([
                    "participant_uuid" => Util::uuid(),
                    "user_id" => $objUser->user_id,
                    "user_uuid" => $objUser->user_uuid,
                    "participating_sid" => $participant->sid,
                    "room_start" => now()
                ]);
            }
        }
        return $arrParticipants;
    }

    public function updateRoomParticipant($objParticipant, $arrData)
    {
        return $this->conferenceParticipantRepository->update($objParticipant, $arrData);
    }

    public function getRoomRecordings($strRoomSid)
    {
        try {
            $recordings = $this->twilioClient->video->v1->rooms($strRoomSid)->recordings->read();
        } catch (\Throwable $th) {
            throw new Exception("Room Recordings not fetched Successfully");
        }


        $arrMediaUrl = [];
        foreach ($recordings as $record) {
            $uri = $record->url . "/Media";
            $response = $this->twilioClient->request("GET", $uri);
            $mediaLocation = $response->getContent()["redirect_to"] ?? "";
            $arrMediaUrl[] = [
                "room_sid"          => $record->roomSid,
                "recording_sid"     => $record->sid,
                "participating_sid" => $record->groupingSids["participant_sid"],
                "media_type"        => $record->type,
                "media_url"         => $uri,
                "media_location"    => $mediaLocation,
                "media_size"        => $record->size,
            ];
        }
        return $arrMediaUrl;
    }



    public function getMediaContent($strFileUrl)
    {
        $strUrlExtension = last(explode(".", explode("?", $strFileUrl)[0]));
        if(empty($strUrlExtension)) {
            return null;
        }
        $tempFilePath = "tmp/" . mt_rand() . ".{$strUrlExtension}";
        if (!file_exists("tmp/")) {
            mkdir("tmp/");
        }
        try {
         fopen($tempFilePath, "w");
        } catch (\Throwable $th) {
          throw new Exception($th->getMessage());
        }

        $fileContents = file_get_contents($strFileUrl);
        file_put_contents($tempFilePath, $fileContents);
        return $tempFilePath;
    }

    public function deleteTempFile()
    {
        $files = glob("tmp/");
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function pingConference($strParticipantUuid)
    {
        $objParticipant = $this->conferenceParticipantRepository->find($strParticipantUuid);
        if (!$objParticipant) {
            throw new Exception("Participant not found");
        }
        $this->updateRoomParticipant($objParticipant, [
            "participant_ping" =>  now(),
        ]);
        return $objParticipant;
    }

    public function removeInactiveParticipants()
    {

        $strTimeOut = now()->addSeconds(-120)->toDateTimeString();
        $objParticipants = $this->conferenceParticipantRepository->getInactiveRoomParticipants($strTimeOut);
        foreach ($objParticipants as $objParticipant) {
            return $this->processInactiveParticipant($objParticipant);
        }
    }

    public function destroyRoomWithInactiveParticipant()
    {
        //-- Get Active Conference rooms
        $objActiveConferences = $this->conferenceRepository->getActiveConferenceRooms();
        if (empty($objActiveConferences)) {
            return false;
        }

        foreach ($objActiveConferences as $objConference) {
            return $this->processActiveConference($objConference);
        }
    }

    protected function processActiveConference($objConference)
    {
        //--Get Room
        $objRoom  = $this->getConferenceRoom($objConference->room_sid);
        //--Check if room is still Active
        if ($objRoom->status == "completed") {
            $arrData = $this->parseRoomNameToUuid($objRoom->uniqueName);
            return disconnectConferenceRoom::dispatch($objRoom,$arrData);
        }
        //--Get Participants
        $objRoomParticipants = $this->getRoomParticipants($objConference->room_sid, Conference::CONNECTED_PARTICIPANTS);
        if ($objRoomParticipants > 1) {
            return false;
        }
        if (count($objRoomParticipants) == 1) {
            //--Compare Last Ping Time to Updated TimeStamp
            $roomParticiPant = $objRoomParticipants[0] ?? null;
            if(!$roomParticiPant) {
                return false;
            }
            $strParticipantUuid = $this->getParticipantUuidFromIdentity($roomParticiPant["identity"]);
            $objParticipant = $this->conferenceParticipantRepository->find($strParticipantUuid);
            //--Get Time Difference
            $timeDifference = now()->diff($objParticipant->stamp_updated_at)->format('%i');
            // return $timeDifference;
            if ($timeDifference < 5) {
                return false;
            }
            //-- Send Notification and Update Paticipant Data
            // $notificationContent = "";
            // $objUser = $this->userService->find($objParticipant->user_id);
            // $arrFlags = [];
            // $objApp = ClientHelper::app();
            // privateNotification::broadcast($objUser, $notificationContent, $arrFlags, $objApp);
            // $this->updateRoomParticipant($objParticipant, ["last_updated_at" => now()]);
        }


    }

    public function getParticipantUuidFromIdentity(String $identity): ?String
    {
        try {
            $objIdentity = json_decode($identity);
        } catch (\Throwable $th) {
            return null;
        }
        return $objIdentity->participant_uuid;
    }

    protected function parseRoomNameToUuid($strRoomName)
    {
        $parseName = explode(".", $strRoomName);
        $strType = strtolower($parseName[1]);
        $strUuid = $parseName[1];
        return [
            $strType . "_uuid" => $strUuid
        ];
    }

    protected function processInactiveParticipant($objParticipant)
    {
        $objConference = $objParticipant->conference;
        $objRoom = $this->getConferenceRoom($objConference->room_sid);
        $this->disconnectRoomParticipant($objRoom->sid, $objParticipant->participating_sid);
        //--Count Active Room Participants
        $getRoomParticipants = $this->getRoomParticipants($objRoom->sid, Conference::CONNECTED_PARTICIPANTS);
        if (count($getRoomParticipants) == 0) {
            $parseName = explode(".", $objConference->room_name);
            $strType = strtolower($parseName[1]);
            $strUuid = $parseName[1];
            $arrUuid = [
                $strType . "_uuid" => $strUuid
            ];
            $this->disconnectRoom($arrUuid);
        }
        if (count($getRoomParticipants) === 1) {
            $notificationContent = "";
            $objUser = $this->userService->find($objParticipant->user_id);
            $arrFlags = [];
            $objApp = ClientHelper::app();
            privateNotification::broadcast($objUser, $notificationContent, $arrFlags, $objApp);
        }
    }

    public function extractRoomData($objRoom)
    {
        $arrOutput = [
            "sid" => $objRoom->sid,
            "status" => $objRoom->status,
            "date_created" => $objRoom->dateCreated,
            "date_updated" => $objRoom->dateUpdated,
            "account_sid" => $objRoom->accountSid,
            "unique_name" => $objRoom->uniqueName,
            "end_time" =>   $objRoom->endTime,
            "duration" => $objRoom->duration,
            "type" => $objRoom->type,
            "max_participants" => $objRoom->maxParticipants,
            "max_concurrent_published_tracks" => $objRoom->maxConcurrentPublishedTracks,
            "record_participants_on_connect" => $objRoom->recordParticipantsOnConnect,
            "video_codecs" => $objRoom->videoCodecs,
            "media_region" => $objRoom->mediaRegion,
            "audio_only" => $objRoom->audioOnly,
            "url" => $objRoom->url,
            "links" => $objRoom->links
        ];

        return (object) $arrOutput;
    }

    public function getProject($uuid, $type)
    {
        if ($type == "project") {
            return $this->projectService->find($uuid);
        } else {
            $objAccount = Account::where("account_uuid", $uuid)->first();
            $objProject = $objAccount->projects->first();
            return $objProject;
        }
    }

    public function getParticipantMediaUrl($strParticipantUuid)
    {
        $objParticipant = $this->conferenceParticipantRepository->find($strParticipantUuid);
        if (!$objParticipant) {
            return "media_file_not_found";
        }
        $objConference = $objParticipant->conference;
        $strRoomName = $objConference->room_name;
        $arrParseName = explode(".", $strRoomName);
        $strConferenceType = strtolower($arrParseName[1]);
        $strUuid = $arrParseName[2];
        $strVideoExtension = last(explode(".", explode("?", $objParticipant->media_video_location)[0]));
        $strAudioExtension = last(explode(".", explode("?", $objParticipant->media_audio_location)[0]));
        //--todo Check If user has permission to record. Return empty array if user does not
        if ($strConferenceType == "project") {
            $objProject = $this->getProject($strUuid, "project");

            return [
                "video" => empty($objParticipant->media_video_location) ? Null : cloud_url("soundblock") . "accounts/{$objProject->account_uuid}/projects/{$objProject->project_uuid}/conference/{$objConference->conference_uuid}_$objParticipant->participant_uuid.{$strVideoExtension}",
                "audio" => empty($objParticipant->media_audio_location) ? Null : cloud_url("soundblock") . "accounts/{$objProject->account_uuid}/projects/{$objProject->project_uuid}/conference/{$objConference->conference_uuid}_$objParticipant->participant_uuid.{$strAudioExtension}"
            ];
        } else {
            $objProject = $this->getProject($strUuid, "account");
            return [
                "video" => empty($objParticipant->media_video_location) ? Null : cloud_url("soundblock") . "accounts/{$objProject->account_uuid}/conference/{$objConference->conference_uuid}_$objParticipant->participant_uuid.{$strVideoExtension}",
                "audio" => empty($objParticipant->media_audio_location) ? Null : cloud_url("soundblock") . "accounts/{$objProject->account_uuid}/conference/{$objConference->conference_uuid}_$objParticipant->participant_uuid.{$strAudioExtension}"
            ];
        }
    }

    public function getUserActiveConferenceRoom($strUserUuid=null) {
        $objUser  = is_null($strUserUuid) ? Auth::user() : $this->userService->find($strUserUuid);
        $objConferenceParticipant = $this->conferenceParticipantRepository->getUserActiveConferenceRoom($objUser->user_uuid);
        if(!$objConferenceParticipant) {
            throw new Exception("User does not belong to an On-going Conference room");
        }
        return $objConferenceParticipant;
    }

}
