<?php

namespace App\Http\Controllers\Soundblock;

use App\Http\Controllers\Controller;
use App\Http\Requests\Soundblock\Video\ConferenceRoom;
use App\Http\Requests\Soundblock\Video\ConferenceRoomSid;
use App\Http\Requests\Soundblock\Video\GetRoomParticipant;
use App\Http\Requests\Soundblock\Video\PingConference;
use App\Models\Users\User;
use Illuminate\Http\Request;
use Twilio\Rest\Client;
use App\Services\Soundblock\Conference as ConferenceService;
use Illuminate\Support\Facades\Auth;

class Conference extends Controller
{
    protected $sid;
    protected $token;
    protected $key;
    protected $secret;
    protected ConferenceService $conferenceService;

    public function __construct(ConferenceService $conferenceService)
    {
        $this->sid = config('services.twilio.sid');
        $this->token = config('services.twilio.token');
        $this->key = config('services.twilio.key');
        $this->secret = config('services.twilio.secret');
        $this->conferenceService = $conferenceService;

//        $this->middleware('isValidConferenceRoom')->only(['connectRoom']);

        if(empty($this->sid) || empty($this->key) || empty($this->secret)){
            throw new \Exception('Some of twilio env value is missing');
        }
    }

    /**
     *
     * This will connect to an existing room
     * in case if room is not existed, it will create one and will return
     * @param ConferenceRoom $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Resources\Json\ResourceCollection|\Illuminate\Http\Response|object
     * @throws \Twilio\Exceptions\ConfigurationException
     * @throws \Twilio\Exceptions\TwilioException
     */
    public function connectRoom(ConferenceRoom $objRequest)
    {
        $strType = is_null($objRequest->project_uuid) ? "account" : "project";
        $strPermissionName = $this->conferenceService->generatePermissionName($strType,"join");
        $strUuid = is_null($objRequest->project_uuid) ? $objRequest->account_uuid : $objRequest->project_uuid;
        $strGroupName = $this->conferenceService->generatePermissionGroupName($strType,$strUuid);

        if(!is_authorized(Auth::user(), $strGroupName, $strPermissionName)){
        return $this->apiReject(null,"You dont have the required permission to Join a Conference Room");
        }

        $resp = $this->conferenceService->joinOrCreateRoom($objRequest->only(['account_uuid','project_uuid']));
        return ($this->apiReply($resp, "", 200));
    }

    /**
     * mark room status as completed,
     * when a room is marked as completed all the participant will be disconnected,
     * or basically it means the room will be closed
     * @param Request $request
     * @throws \Twilio\Exceptions\ConfigurationException
     * @throws \Twilio\Exceptions\TwilioException
     */
    public function disconnectRoom(ConferenceRoom $request)
    {
        $strRoomSid = $this->conferenceService->disconnectRoom($request->only(['account_uuid','project_uuid']));
        return ($this->apiReply(["room_sid" => $strRoomSid], "Room Disconnected Successfully"));
    }

    /**
     * return room details by passing the room name
     * @param Request $request
     * @throws \Twilio\Exceptions\ConfigurationException
     * @throws \Twilio\Exceptions\TwilioException
     */
    public function roomDetails(ConferenceRoom $request)
    {
        $strUniqueRoomName = $this->conferenceService->generateRoomName($request->only(['account_uuid','project_uuid']));
        $twilio = new Client($this->sid, $this->token);
        $exists = $twilio->video->rooms->read([ 'uniqueName' => $strUniqueRoomName]);
        $status = 'completed';
        $strRoomSid = "";
        if($exists){
            $room = current($exists);
            $status = $room->status;
            $strRoomSid =  $room->sid;
        }
        $resp = [
            'room_name' => $strUniqueRoomName,
            'room_sid'  => $strRoomSid,
            'status'    => $status,
        ];
        return ($this->apiReply($resp, "", 200));
    }

    public function getRoomBySid($strRoomSid){
    $objRoom = $this->conferenceService->getConferenceRoom($strRoomSid);
    return (array) $this->conferenceService->extractRoomData($objRoom);
    }

    /**
     *  Return currently active rooms
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Resources\Json\ResourceCollection|\Illuminate\Http\Response|object
     * @throws \Twilio\Exceptions\ConfigurationException
     */
    public function getRooms()
    {
        $arrAuthorizedRoomList = $this->conferenceService->getAuthorizedRoomList();
        return ($this->apiReply($arrAuthorizedRoomList, "", 200));
//        return response()->json($arrAuthorizedRoomList, 200);
    }

    /**
     * Return list of recordings available for a room
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Resources\Json\ResourceCollection|\Illuminate\Http\Response|object
     * @throws \Twilio\Exceptions\ConfigurationException
     */
    public function getRoomRecordings(ConferenceRoomSid $objRequest)
    {
        $arrRecordings = $this->conferenceService->getRoomRecordings($objRequest->room_sid);
        return $this->apiReply($arrRecordings);
    }

    public function getRoomParticipants(ConferenceRoomSid $objRequest){
    return $this->conferenceService->getRoomParticipants($objRequest->room_sid);
    }

    public function getRoomParticipant(GetRoomParticipant $objRequest)
    {
        return $this->conferenceService->getRoomParticipant($objRequest->room_sid, $objRequest->participating_sid);
    }

    public function disconnectRoomParticipant(GetRoomParticipant $objRequest)
    {
        return $this->conferenceService->disconnectRoomParticipant($objRequest->room_sid, $objRequest->participating_sid);
    }

    public function pingConference(PingConference $objRequest){
    $this->conferenceService->pingConference($objRequest->participant_uuid);
    return $this->apiReply(null,"Ping Successful");
    }

    public function removeInactiveParticipants(){
        return $this->conferenceService->destroyRoomWithInactiveParticipant();
    }

    public function getParticipantMediaUrl($strParticipantUuid){
    return $this->conferenceService->getParticipantMediaUrl($strParticipantUuid);
    }

}
