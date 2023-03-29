<?php

namespace App\Http\Controllers\Core\Services\Support;

use App\Helpers\Util;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Client;
use App\Helpers\Builder;
use Exception;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Mail\System\Notification\Ticket as TicketMail;
use App\Models\Support\Ticket\SupportTicket as SupportTicketModel;
use Illuminate\Support\Facades\Mail;
use App\Repositories\{
    Core\Auth\AuthGroup as AuthGroupRepo,
    Core\Auth\AuthPermission as AuthPermissionRepo,
};
use App\Services\{
    User as UserService,
    Core\Auth\AuthGroup,
    Office\SupportTicket as SupportTicketService,
    Common\App as AppService
};
use App\Http\Requests\{
    Core\Services\Support\CoreGetAllSupport,
    Core\Services\Support\DetachUser,
    Core\Services\Support\CreateCoreTicket,
    Office\Support\UpdateSupportTicket,
    Core\Services\Support\AttachUser
};

/**
 * @group Core Support
 *
 */
class Ticket extends Controller {
    /** @var SupportTicketService */
    private SupportTicketService $ticketService;
    /** @var AppService */
    private AppService $appService;
    /**
     * @var AuthGroupRepo
     */
    private AuthGroupRepo $authGroupRepo;
    /**
     * @var AuthPermissionRepo
     */
    private AuthPermissionRepo $authPermissionRepo;

    /**
     * @param SupportTicketService $ticketService
     * @param AppService $appService
     * @param AuthGroupRepo $authGroupRepo
     * @param AuthPermissionRepo $authPermissionRepo
     */
    public function __construct(SupportTicketService $ticketService, AppService $appService,
                                AuthGroupRepo $authGroupRepo, AuthPermissionRepo $authPermissionRepo) {
        $this->ticketService = $ticketService;
        $this->appService = $appService;
        $this->authGroupRepo = $authGroupRepo;
        $this->authPermissionRepo = $authPermissionRepo;
    }

    /**
     * @param CoreGetAllSupport $objRequest
     * @return object
     */
    public function index(CoreGetAllSupport $objRequest) {
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

            $arrTickets = $this->ticketService->findAllForOfficeByApps($objRequest->all(), $arrAllowedApps, $objRequest->input("per_page", 10));
        } else {
            $arrParams = $objRequest->all();

            if (!$objRequest->exists("flag_status")) {
                $arrParams["flag_status"] = "All";
            }

            [$arrTickets, $arrMeta] = $this->ticketService->findAll($arrParams, $objRequest->input("per_page", 10), null, $objUser);
        }

        return ($this->apiReply($arrTickets));
    }

    /**
     * @param string $ticket
     * @return object
     * @throws Exception
     */
    public function show(string $ticket) {
        $objTicket = $this->ticketService->find($ticket);

        if (!$this->ticketService->checkTicketEqualUser($objTicket, Auth::user())) {
            abort(404, "Users ticket not found.");
        }

        $objTicket->load([
            "support"  => function ($query) {
                $query->with("app");
            },
            "messages" => function ($query) {
                $query->where("flag_office", false);
                $query->orderBy(SupportTicketModel::STAMP_CREATED, "asc");
            },
            "messages.attachments",
        ]);

        return ($this->apiReply($objTicket));
    }

    /**
     * @param CreateCoreTicket $objRequest
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|Response|object
     * @throws Exception
     */
    public function store(CreateCoreTicket $objRequest) {
        $objApp = Client::app();
        $objAuthUser = Auth::user();

        if ($this->ticketService->checkTicketDuplicate($objAuthUser->user_uuid, $objRequest->input("title"), $objRequest->input("message.text"))) {
            return ($this->apiReject(null, "Ticket already exists.", Response::HTTP_BAD_REQUEST));
        }

        [$objTicket, $objMsg] = $this->ticketService->creteCoreTicket($objAuthUser, $objApp, $objRequest->all(), $objApp->app_name == "office");

        $arrPermissionsApps = config("constant.support.apps_types_permissions");
        $strPermission = $arrPermissionsApps[strtolower($objApp->app_name)];
        $objUsers = Util::getUsersByPermissionAndGroup("Arena.Support", $strPermission);

        foreach ($objUsers as $objUser) {
            $strEmail = $objUser->primary_email->user_auth_email;

            if (config("app.env") == "prod" && $strEmail == "devans@arena.com") {
                continue;
            }

            Mail::to($strEmail)->send(new TicketMail($objTicket, "Support Ticket: " . $objTicket->user->name . ": " . $objTicket->ticket_title, $objMsg));
        }

        /* Send Notification */
        $strAppName = ucfirst($objApp->app_name);
        $objApp = $this->appService->findOneByName("office");
        $strMemo = "&quot;{$objTicket->ticket_title}&quot; by {$objAuthUser->name} <br> {$strAppName} &bull; {$objTicket->support->support_category}";
        $strUrl = app_url("office") . "customers/support/tickets/" . $objTicket->ticket_uuid;

        notify_group_permission("Arena.Support", $strPermission, $objApp, "New Support Ticket", $strMemo, Builder::notification_link([
            "link_name" => "View Ticket",
            "url"       => $strUrl,
        ]), $strUrl);

        return (
            $this->apiReply($objTicket->load("support", "user", "supportUser", "supportGroup", "messages", "messages.attachments"),
                "", 201)
        );
    }

    /**
     * @param UpdateSupportTicket $objRequest
     * @return object
     * @throws Exception
     */
    public function update(UpdateSupportTicket $objRequest) {
        $objTicket = $this->ticketService->find($objRequest->ticket);

        if ( !is_authorized(Auth::user(), "Arena.Office", "Arena.Office.Access", "office") ) {
            if (!$this->ticketService->checkTicketEqualUser($objTicket, Auth::user())) {
                abort(404, "Users ticket not found.");
            }
        }

        $objTicket = $this->ticketService->update($objTicket, $objRequest->all());

        return ($this->apiReply($objTicket->load("support")));
    }

    /**
     * @param AttachUser $request
     * @param string $ticket
     * @param UserService $userService
     * @param AuthGroup $authGroupService
     * @return object
     * @throws Exception
     */
    public function attachMember(AttachUser $request, string $ticket, UserService $userService, AuthGroup $authGroupService) {
        $objApp = Client::app();

        try {
            /** @var \App\Models\Support\Ticket\SupportTicket */
            $objTicket = $this->ticketService->find($ticket, true);
            $user = Auth::user();
            $userGroups = $authGroupService->findByUser($user->user_uuid, 10, false);
            $groupIds = $userGroups->pluck("group_id");

            if ($this->ticketService->checkAccessToTicket($objTicket, $user, $groupIds)) {
                if ($request->has("user")) {
                    $users = $userService->findAllWhere([$request->input("user")]);
                    $this->ticketService->attachUsers($objTicket, $users);
                }

                if ($request->has("group") && $objApp->app_name == "office") {
                    $group = $authGroupService->findAllWhere([$request->input("group")]);
                    $this->ticketService->attachGroup($objTicket, $group);
                }
            } else {
                abort(403, "You have not access to this ticket");
            }

            return ($this->apiReply($objTicket->load("support")));
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $ticket
     * @param DetachUser $request
     * @param UserService $userService
     * @param AuthGroup $authGroupService
     * @return object
     * @throws Exception
     */
    public function detachMember($ticket, DetachUser $request, UserService $userService, AuthGroup $authGroupService) {
        $objApp = Client::app();

        try {
            $objTicket = $this->ticketService->find($ticket);
            $user = Auth::user();
            $userGroups = $authGroupService->findByUser($user->user_uuid, 10, false);
            $groupIds = $userGroups->pluck("group_id");

            if ($objApp->app_name == "office" && $this->ticketService->checkAccessToTicket($objTicket, $user, $groupIds, true)) {
                if ($request->has("user")) {
                    $users = $userService->findAllWhere([$request->input("user")]);
                    $this->ticketService->detachUser($objTicket, $users);
                }

                if ($request->has("group")) {
                    $group = $authGroupService->find([$request->input("group"), true]);
                    $this->ticketService->detachGroup($objTicket, $group);
                }
            } else {
                abort(403, "You have not access to this ticket");
            }

            return ($this->apiReply($objTicket->load("support")));
        } catch (Exception $e) {
            throw $e;
        }
    }
}
