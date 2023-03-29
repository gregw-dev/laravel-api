<?php

namespace App\Http\Controllers\Office;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\{
    Core\UpdateCorrespondence,
    Office\Correspondence\GetCorrespondences,
    Office\Correspondence\ResponseCorrespondence,
    Office\Correspondence\CreateCorrespondence,
    Office\Correspondence\CorrespondenceUser,
    Office\Correspondence\RemoveCorrespondenceUser,
    Office\Correspondence\CorrespondenceEmail,
    Office\Correspondence\RemoveCorrespondenceEmail,
    Office\Correspondence\CorrespondenceGroup,
    Office\Correspondence\RemoveCorrespondenceGroup,
    Office\Correspondence\GetCorrespondenceMessages,
};
use App\Services\Core\Correspondence as CorrespondenceService;
use App\Repositories\Core\Correspondence as CorrespondenceRepository;
use App\Services\User as UserService;
use App\Repositories\Core\Auth\AuthGroup as AuthGroupRepository;
use App\Repositories\Core\Auth\AuthPermission as AuthPermissionRepository;

/**
 * @group Office Correspondence
 *
 */
class Correspondence extends Controller
{
    /** @var CorrespondenceService */
    private CorrespondenceService $correspondenceService;
    /** @var CorrespondenceRepository */
    private CorrespondenceRepository $correspondenceRepository;
    /** @var UserService */
    private UserService $userService;
    /** @var AuthGroupRepository */
    private AuthGroupRepository $authGroupRepo;
    /** @var AuthPermissionRepository */
    private AuthPermissionRepository $authPermissionRepo;

    /**
     * Correspondence constructor.
     * @param CorrespondenceService $correspondenceService
     * @param CorrespondenceRepository $correspondenceRepository
     * @param UserService $userService
     * @param AuthGroupRepository $authGroupRepo
     * @param AuthPermissionRepository $authPermissionRepo
     */
    public function __construct(CorrespondenceService $correspondenceService, CorrespondenceRepository $correspondenceRepository,
                                UserService $userService, AuthGroupRepository $authGroupRepo,
                                AuthPermissionRepository $authPermissionRepo){
        $this->correspondenceService    = $correspondenceService;
        $this->correspondenceRepository = $correspondenceRepository;
        $this->userService = $userService;
        $this->authGroupRepo = $authGroupRepo;
        $this->authPermissionRepo = $authPermissionRepo;
    }

    /**
     * @param GetCorrespondences $objRequest
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|Response|object
     */
    public function getCorrespondence(GetCorrespondences $objRequest){
        $objUser = Auth::user();
        $objGroup = $this->authGroupRepo->findByName("Arena.Support");
        $objPermissions = $this->authPermissionRepo->findAllByUserAndGroup($objUser, $objGroup);
        $arrPermissionsApps = config("constant.support.apps_types_permissions");
        $arrApps = [];

        foreach ($objPermissions as $objPermission) {
            $arrApps = array_merge($arrApps, array_keys($arrPermissionsApps, $objPermission->permission_name));
        }

        $arrAllowedApps = array_values(array_filter($arrApps));

        $isSuperuser = is_authorized($objUser, "Arena.Superusers", "Superuser", "office");

        if ($isSuperuser) {
            $arrAllowedApps = array_keys($arrPermissionsApps);
        } else {
            if ($objPermissions->count() == 0 || empty($arrAllowedApps)) {
                return ($this->apiReply(null, "", Response::HTTP_FORBIDDEN));
            }
        }

        [$objCorrespondence, $availableMetaData] = $this->correspondenceService->findAllWithAllowedApps($objRequest->input("per_page", 10), $objRequest->all(), $arrAllowedApps);

        return ($this->apiReply($objCorrespondence, "", Response::HTTP_OK, $availableMetaData));
    }

    /**
     * @param CreateCorrespondence $objRequest
     * @return
     */
    public function createCorrespondence(CreateCorrespondence $objRequest){
        if (!is_authorized(Auth::user(), "Arena.Office", "Arena.Office.Access", "office")) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        $attachments = $this->correspondenceService->combineAttachments($objRequest->file());
        $clientIp    = $objRequest->ip();
        $clientHost  = $objRequest->getHttpHost();
        $clientAgent  = $objRequest->server("HTTP_USER_AGENT");

        if(filter_var($objRequest->to, FILTER_VALIDATE_EMAIL)){
        $strUserEmail = $objRequest->to;
        }else{
            $strUserEmail = $this->userService->find($objRequest->to)->primary_email->user_auth_email;
        }
       $bnHasDuplicate = $this->correspondenceService->checkDuplicate($strUserEmail,
            $objRequest->input("subject"), $objRequest->input("text"),"text");
        if ($bnHasDuplicate) {
            return ($this->apiReject(null, "Duplicate Record.", 400));
        }

        $arrData =$objRequest->only(["subject","text","user","users","groups","emails","confirmation_page","from"]);
        $arrData["email"] = $strUserEmail;
        $objCorrespondence = $this->correspondenceService->create($arrData, $clientIp, $clientHost,$clientAgent, $attachments);

        if (is_null($objCorrespondence)) {
            return ($this->apiReject(null, "Correspondence hasn't created.", 400));
        }

        return ($this->apiReply($objCorrespondence, "Correspondence created successfully.", 200));
    }

    /**
     * @param string $correspondenceUUID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCorrespondenceByUuid(string $correspondenceUUID){
        if (!is_authorized(Auth::user(), "Arena.Office", "Arena.Office.Access", "office")) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        $objCorrespondence = $this->correspondenceRepository->findByUuid($correspondenceUUID);
        if(!$objCorrespondence){
          return $this->apiReject(null, "Correspondence not found");
        }
        $arrResponse = $objCorrespondence->toArray();
        return $this->apiReply($arrResponse,"Correspondence Retrieved Successfully");
    }

    /**
     * @param string $correspondenceUUID
     * @param UpdateCorrespondence $request
     * @return mixed
     */
    public function updateCorrespondenceByUuid(string $correspondenceUUID, UpdateCorrespondence $request){
        if (!is_authorized(Auth::user(), "Arena.Office", "Arena.Office.Access", "office")) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        $boolResult = $this->correspondenceRepository->updateByUuid(
            $correspondenceUUID,
            $request->only(["flag_archived", "flag_deleted"])
        );

        if ($boolResult) {
            return ($this->apiReply(null, "Correspondence updated successfully."));
        }

        return ($this->apiReject(null, "Correspondence hasn't updated."));
    }

    /**
     * @param string $correspondenceUUID
     * @param ResponseCorrespondence $objRequest
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|Response|object
     * @throws \Exception
     */
    public function responseForCorrespondence(string $correspondenceUUID, ResponseCorrespondence $objRequest){
        if (!is_authorized(Auth::user(), "Arena.Office", "Arena.Office.Access", "office")) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }
        $strClientHost  = $objRequest->getHttpHost();
        $strClientIp  = $objRequest->ip();
        $strClientAgent  = $objRequest->header("User-Agent");
        $arrData = $objRequest->all();
        $arrData["attachments"] = $this->correspondenceService->combineAttachments($objRequest->file());
        $objMessage = $this->correspondenceService->responseForCorrespondence($correspondenceUUID, $arrData, $strClientHost,$strClientIp, $strClientAgent);

        if ($objMessage) {
            return ($this->apiReply($objMessage, "Response sent successfully."));
        }

        return ($this->apiReject(null, "Response hasn't sent."));
    }

    public function deleteCorrespondence(string $correspondenceUuid){
        if (!is_authorized(Auth::user(), "Arena.Office", "Arena.Office.Access", "office")) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        if($this->correspondenceService->deleteCorrespondence($correspondenceUuid)){
            return $this->apiReply(null,"Correspondence Deleted Successfully");
        }
    }

    public function getCorrespondenceMessages(GetCorrespondenceMessages $objRequest, String $strCorrespondenceUuid){
        if (!is_authorized(Auth::user(), "Arena.Office", "Arena.Office.Access", "office")) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }
        if(!$this->correspondenceRepository->find($strCorrespondenceUuid)){
         return $this->apiReject(null, "Correspondence Not Found",404);
        }
        [$objMessages, $availableMetaData ]= $this->correspondenceService->getCorrespondenceMessages($strCorrespondenceUuid,$objRequest->input("per_page",10));
        return $this->ApiReply($objMessages,"Correspondence messages loaded Successfully", Response::HTTP_OK ,$availableMetaData);
    }

    public function addUserToCorrespondence(CorrespondenceUser $objRequest) {
        $objCorrespondence = $this->correspondenceService->getCorrespondence($objRequest->correspondence_uuid);
        $arrUsers = $this->correspondenceService->storeCorrespondenceUsers($objCorrespondence,$objRequest->user_uuid);
         return $this->apiReply($arrUsers,"User Added Successfully to correspondence");
        }

    public function removeUserFromCorrespondence(RemoveCorrespondenceUser $objRequest) {
    $objCorrespondence = $this->correspondenceService->getCorrespondence($objRequest->correspondence_uuid);
     $this->correspondenceService->removeCorrespondenceUsers($objCorrespondence,[$objRequest->user_uuid]);
     return $this->apiReply($objCorrespondence->users,"User removed successfully from correspondence");
    }

    public function addEmailToCorrespondence(CorrespondenceEmail $objRequest)
    {

        $objCorrespondence = $this->correspondenceService->getCorrespondence($objRequest->correspondence_uuid);
       $arrResponse = $this->correspondenceService->storeCorrespondenceEmail($objCorrespondence, $objRequest->email);
        return $this->apiReply($arrResponse, "Email Address added successfully to correspondence");
    }

    public function removeEmailFromCorrespondence(RemoveCorrespondenceEmail $objRequest)
    {
        $objCorrespondence = $this->correspondenceService->getCorrespondence($objRequest->correspondence_uuid);
        $this->correspondenceService->removeCorrespondenceEmail($objCorrespondence, [$objRequest->email]);
        return $this->apiReply($objCorrespondence->emails, "Email Address removed successfully from correspondence");
    }

    public function addGroupToCorrespondence(CorrespondenceGroup $objRequest)
    {
        $objCorrespondence = $this->correspondenceService->getCorrespondence($objRequest->correspondence_uuid);
        $arrGroups = $this->correspondenceService->storeCorrespondenceGroup($objCorrespondence, $objRequest->group_uuid);
        return $this->apiReply($arrGroups, "Group added successfully to correspondence");
    }

    public function removeGroupFromCorrespondence(RemoveCorrespondenceGroup $objRequest)
    {
        $objCorrespondence = $this->correspondenceService->getCorrespondence($objRequest->correspondence_uuid);
        $this->correspondenceService->removeCorrespondenceGroup($objCorrespondence, [$objRequest->group_uuid]);
        return $this->apiReply($objCorrespondence->groups, "Group removed successfully from correspondence");
    }

}
