<?php

namespace App\Http\Controllers\Core\Services\Support;

use App\Helpers\Client;
use Exception;
use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Services\{
    Office\SupportTicketMessage as SupportTicketMessageService,
    Office\SupportTicket
};
use App\Http\{Controllers\Controller,
    Requests\Office\Support\CreateSupportTicketMessage,
    Transformers\Office\Support\SupportTicketMessage as SupportTicketMessageTransformer
};
use App\Repositories\Core\Auth\AuthGroup as AuthGroupRepository;
use App\Repositories\Core\Auth\AuthPermission as AuthPermissionRepository;

/**
 * @group Core Support
 *
 */
class SupportTicketMessage extends Controller {
    /** @var SupportTicket */
    private SupportTicket $ticketService;
    /** @var SupportTicketMessageService */
    private SupportTicketMessageService $msgService;
    /** @var AuthGroupRepository */
    private AuthGroupRepository $authGroupRepo;
    /** @var AuthPermissionRepository */
    private AuthPermissionRepository $authPermissionRepo;

    /**
     * @param SupportTicket $ticketService
     * @param SupportTicketMessageService $msgService
     * @param AuthGroupRepository $authGroupRepo
     * @param AuthPermissionRepository $authPermissionRepo
     */
    public function __construct(SupportTicket $ticketService, SupportTicketMessageService $msgService,
                                AuthGroupRepository $authGroupRepo, AuthPermissionRepository $authPermissionRepo) {
        $this->ticketService = $ticketService;
        $this->msgService = $msgService;
        $this->authGroupRepo = $authGroupRepo;
        $this->authPermissionRepo = $authPermissionRepo;
    }

    /**
     * @param string $ticket
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response|object
     */
    public function index(string $ticket, Request $request) {
        /** @var User $objUser */
        $objUser = Auth::user();
        $appOffice = Client::app()->app_name === "office";

        if ($appOffice) {
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

            $objTicket = $this->ticketService->checkTicketUserForOffice($arrAllowedApps, $ticket);

            if (!$objTicket) {
                return ($this->apiReject(null, "Ticket not found.", Response::HTTP_BAD_REQUEST));
            }
        } else {
            $objTicket = $this->ticketService->checkTicketUserForCore($objUser, $ticket);

            if (!$objTicket) {
                return ($this->apiReject(null, "Ticket not found.", 400));
            }
        }

        [$messages, $availableMetaData] = $this->msgService->getMessages($request->all(), $objTicket, $request->input("per_page", 10), true, $appOffice);

        return ($this->paginator($messages, new SupportTicketMessageTransformer(["attachments"])));
    }

    public function getUserUnreadSupportMessages(){
        $objUser = Auth::user();
        $unreadUserMessages = $this->msgService->getUserUnreadMessages($objUser);
        return ($this->apiReply(["count_messages" => $unreadUserMessages->count()], "", Response::HTTP_OK));
    }

    /**
     * @param CreateSupportTicketMessage $objRequest
     * @param SupportTicketMessageService $msgService
     * @return \Illuminate\Http\JsonResponse
     * @throws Exception
     */
    public function storeMessage(CreateSupportTicketMessage $objRequest, SupportTicketMessageService $msgService) {
        $objApp = Client::app();

        if ($objApp->app_name == "office" && !is_authorized(Auth::user(), "Arena.Office", "Arena.Office.Access", "office")) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        $objTicket = $this->ticketService->find($objRequest->input("ticket"));

        if ($objTicket->flag_status == "Closed") {
            return ($this->apiReject(null, "Ticket is closed.", Response::HTTP_BAD_REQUEST));
        }

        if (
            $msgService->checkDuplicateMessage(
                $objRequest->input("ticket"),
                $objRequest->input("user"),
                $objRequest->input("message_text")
            )
        ) {
            return ($this->apiReject(null, "Duplicated message.", Response::HTTP_BAD_REQUEST));
        }

        $bnOffice = $objApp->app_name == "office";
        $objMsg = $msgService->create($bnOffice, $objRequest->all());

        return ($this->item($objMsg, new SupportTicketMessageTransformer(["attachments"])));
    }
}
