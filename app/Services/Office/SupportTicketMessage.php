<?php

namespace App\Services\Office;

use App\Events\{Common\PrivateNotification,
    Common\UpdateSupportTicket,
    Office\Support\TicketAttach,
    User\UserBroadcast
};
use App\Mail\Core\Support\TicketReply;
use App\Mail\System\Notification\Ticket as TicketMail;
use App\Models\Support\Ticket\SupportTicket;
use App\Models\Users\User as UserModel;
use App\Repositories\{Core\Auth\AuthGroup as AuthGroupRepository,
    Office\SupportTicket as SupportTicketRepository,
    Office\SupportTicketMessage as SupportTicketMessageRepository,
    User\User
};
use App\Services\Common\{App as AppService, Office};
use Illuminate\Support\Facades\Auth;
use App\Helpers\Builder;
use App\Helpers\Client;
use Illuminate\Support\Facades\Mail;
use App\Helpers\Util;
use App\Jobs\Office\UpdateSupportTicketMessages;

class SupportTicketMessage
{
    protected User $userRepo;
    protected SupportTicketMessageRepository $msgRepo;
    protected SupportTicketRepository $ticketRepo;
    protected Office $officeService;
    /** @var \App\Services\Common\App */
    private AppService $appService;
    /** @var AuthGroupRepository */
    private AuthGroupRepository $authGroupRepo;

    /**
     * SupportTicketMessage constructor.
     * @param \App\Repositories\Office\SupportTicketMessage $msgRepo
     * @param \App\Repositories\Office\SupportTicket $ticketRepo
     * @param \App\Repositories\User\User $userRepo
     * @param \App\Services\Common\Office $officeService
     * @param \App\Services\Common\App $appService
     * @param AuthGroupRepository $authGroupRepo
     */
    public function __construct(SupportTicketMessageRepository $msgRepo, SupportTicketRepository $ticketRepo,
                                User $userRepo, Office $officeService, AppService $appService, AuthGroupRepository $authGroupRepo) {
        $this->msgRepo = $msgRepo;
        $this->ticketRepo = $ticketRepo;
        $this->userRepo = $userRepo;
        $this->officeService = $officeService;
        $this->appService = $appService;
        $this->authGroupRepo = $authGroupRepo;
    }

    public function getMessages(array $arrParams, SupportTicket $ticket, int $perPage = 10, bool $withoutOffice = false, bool $flagOffice = false) {
        [$objMessages, $availableMetaData] = $this->msgRepo->getMessages($arrParams, $ticket, $perPage, $withoutOffice);

        UpdateSupportTicketMessages::dispatch($objMessages,$flagOffice);

        return ([$objMessages, $availableMetaData]);
    }

    public function getUserUnreadMessages($objUser){
        return ($this->msgRepo->getUserUnreadMessages($objUser->user_uuid));
    }

    public function checkDuplicateMessage(string $uuidTicket, string $uuidUser, string $messageText){
        return ($this->msgRepo->checkDuplicateMessageText($uuidTicket, $uuidUser, $messageText));
    }

    public function create(bool $bnOffice, array $arrParams, bool $bnInCreation = false, $objApp = null) {
        $arrTicketMsg = [];

        if (is_null($objApp)) {
            $objApp = Client::app();
        }

        if ($arrParams["ticket"] instanceof SupportTicket) {
            $objTicket = $arrParams["ticket"];
        } else {
            $objTicket = $this->ticketRepo->find($arrParams["ticket"], true);
        }

        if (is_null($objApp) && $objTicket->ticket_title == "Soundblock Charge Failed") {
            $objApp = $this->appService->findOneByName("soundblock");
        }

        $arrPermissionsApps = config("constant.support.apps_types_permissions");
        $strPermission = $arrPermissionsApps[strtolower($objApp->app_name)];
        $objUsersNotify = Util::getUsersByPermissionAndGroup("Arena.Support", $strPermission);

        if (isset($arrParams["user"])) {
            if ($arrParams["user"] instanceof UserModel) {
                $objUser = $arrParams["user"];
            } else {
                $objUser = $this->userRepo->find($arrParams["user"], true);
            }
        } else {
            $objUser = Auth::user();
        }

        $arrTicketMsg["ticket_id"] = $objTicket->ticket_id;
        $arrTicketMsg["ticket_uuid"] = $objTicket->ticket_uuid;
        $arrTicketMsg["user_id"] = $objUser->user_id;
        $arrTicketMsg["user_uuid"] = $objUser->user_uuid;
        $arrTicketMsg["message_text"] = $arrParams["message_text"];
        $arrTicketMsg["flag_office"] = $bnOffice;
        $arrTicketMsg["flag_notified"] = false; // default
        $arrTicketMsg["flag_status"] = "Unread";

        if ($bnOffice) {
            $arrTicketMsg["flag_officeonly"] = $arrParams["flag_officeonly"];
        } else {
            $arrTicketMsg["flag_officeonly"] = false;
        }

        if (isset($arrParams["files"])) {
            $arrTicketMsg["flag_attachments"] = true;
            $objMsg = $this->msgRepo->create($arrTicketMsg);
            $arrMeta = $this->upload($objMsg->ticket, $arrParams["files"]);

            event(new TicketAttach($objMsg, $arrMeta));
        } else {
            $arrTicketMsg["flag_attachments"] = false;
            $objMsg = $this->msgRepo->create($arrTicketMsg);
        }

        $objTicket->update([
            "flag_status" => $bnOffice ? "Awaiting Customer" : "Awaiting Support"
        ]);

        event(new UpdateSupportTicket($objTicket, $bnOffice ? "soundblock" : "office"));
        // broadcast pusher notification
        $this->broadcastUnreadMessage($objUser, $objTicket);

        $objSupport = $objTicket->support;
        $objSupportApp = $objSupport->app;

        if ($bnOffice && !$arrTicketMsg["flag_officeonly"] && !$bnInCreation) {
            $arrMsg = [
                "notification_name" => ucfirst($objSupportApp->app_name),
                "notification_memo" => "Support Ticket Reply",
                "ticket_message" => $arrParams["message_text"],
                "autoClose"         => true,
                "showTime"          => 5000,
            ];

            if (ucfirst($objSupportApp->app_name) == "Soundblock") {
                $arrMsg["ticket_url"] = app_url($objSupportApp->app_name) . "support?ticket_id=" . $objTicket->ticket_uuid;
            }

            $flags = [
                "notification_state" => "unread",
                "flag_canarchive"    => true,
                "flag_candelete"     => true,
                "flag_email"         => false,
            ];

            event(new PrivateNotification($objTicket->user, $arrMsg, $flags, $objApp));
        } else {
            $strAppName = ucfirst($objSupportApp->app_name);
            $strMemo = "&quot;{$objTicket->ticket_title}&quot; by {$objUser->name} <br> {$strAppName} &bull; {$objTicket->support->support_category}";
            $strUrl = app_url("office") . "customers/support/tickets/" . $objTicket->ticket_uuid;
            $objOfficeApp = $this->appService->findOneByName("office");

            notify_group_permission("Arena.Support", $strPermission, $objOfficeApp, "Support Ticket Reply", $strMemo, Builder::notification_link([
                "link_name" => "View Ticket",
                "url"       => $strUrl,
            ]), $strUrl);
        }

        if (!$bnInCreation) {
            foreach ($objUsersNotify as $objUser) {
                $strEmail = $objUser->primary_email->user_auth_email;

                if (config("app.env") == "prod" && $strEmail == "devans@arena.com") {
                    continue;
                }

                Mail::to($strEmail)->send(new TicketMail(
                    $objTicket,
                    "Support Ticket Reply",
                    $objMsg,
                    "Support Ticket: {$objTicket->user->name} : {$objTicket->ticket_title}"
                ));
            }

            Mail::to($objTicket->user->primary_email->user_auth_email)->send(new TicketReply($objTicket));
        }

        return ($objMsg);
    }

    public function ingestMessage(Array $arrData ) {
        if (
            $this->checkDuplicateMessage(
                $arrData["ticket"],
                $arrData["user"],
                $arrData["message_text"]
            )
        ) {
           throw new \Exception("Duplicate message Detected During Message Ingestion from mail");
        }
        $objApp = $this->appService->findOneByName("office");
        $objMsg = $this->create(false,  $arrData,false,$objApp);
        if($objMsg){
            return http_response_code(200);
        }else{
            return http_response_code(401);
        }
    }

    private function broadcastUnreadMessage(UserModel $objUser, SupportTicket $objTicket)
    {

        /**
         * calling private-user-notification-{user_uuid} event*
         * this broadcast channel will be used to send different types of message to the user
         */

        $arrAssociatedUsers = $objTicket->supportUser()->get();
        $objSupportGroups = $objTicket->supportGroup()->get();
        foreach ($objSupportGroups as $objSupportGroup) {
            foreach ($objSupportGroup->users()->get() as $objSupportGroupUser) {
                $arrAssociatedUsers->push($objSupportGroupUser);
            }
        }
        // exclude sender
        $arrAssociatedUsers->unique('user_uuid')->keyBy('user_uuid')->forget($objUser->user_uuid);
        foreach ($arrAssociatedUsers as $objAssociatedUser) {
            $arrPayload = [
                "count" => $this->getUserUnreadMessages($objAssociatedUser)->count()
            ];
            event(new UserBroadcast($objAssociatedUser->user_uuid, "Arena", "tickets/unread", $arrPayload));
        }

    }

    public function upload(SupportTicket $objTicket, array $files): array
    {
        $arrMeta = [];

        foreach ($files as $file) {
            $meta = [];
            $path = Util::ticket_path($objTicket);
            $meta["attachment_url"] = $this->officeService->putFile($file, $path);
            $meta["attachment_name"] = $file->getClientOriginalName();

            array_push($arrMeta, $meta);
        }

        return ($arrMeta);
    }


}
