<?php

namespace App\Services\Core;

use App\Helpers\Builder;
use App\Helpers\Util;
use App\Helpers\Client;
use ZipArchive;
use App\Helpers\Filesystem\Merch;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Repositories\User\UserContactEmail;
use App\Repositories\Core\CorrespondenceAttachments;
use App\Mail\Core\Correspondence as CorrespondenceMail;
use App\Repositories\Core\Auth\AuthGroup as AuthGroupRepository;
use App\Repositories\Core\Auth\AuthPermission as AuthPermissionRepository;
use App\Repositories\Core\Correspondence as CorrespondenceRepository;
use App\Mail\Core\CorrespondenceResponse as CorrespondenceResponseMail;
use App\Mail\Core\CorrespondenceConfirmation as CorrespondenceConfirmationMail;
use App\Repositories\Core\CorrespondenceResponses as CorrespondenceResponsesRepository;
use App\Repositories\Core\CorrespondenceMessages as CorrespondenceMessagesRepository;
use App\Repositories\Core\CorrespondenceUsers as CorrespondenceUsersRepository;
use App\Repositories\Core\CorrespondenceEmails as CorrespondenceEmailsRepository;
use App\Repositories\Core\CorrespondenceGroups as CorrespondenceGroupsRepository;
use App\Repositories\Core\CorrespondenceLookup as CorrespondenceLookupRepository;
use App\Repositories\Office\SupportTicketLookup;
use App\Services\Office\SupportTicketMessage;
use App\Services\Common\App as AppService;
use Exception;
use Illuminate\Support\Str;
use App\Http\Resources\Core\CorrespondenceMessage as CorrespondenceMessageResource;
use App\Services\Core\Auth\AuthGroup as AuthGroupService;
use App\Services\User as UserService;
use App\Services\Email as EmailService;
use App\Models\Core\CorrespondenceMessage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class Correspondence
{
    /** @var CorrespondenceRepository */
    private CorrespondenceRepository $correspondenceRepository;
    /** @var UserContactEmail */
    private UserContactEmail $contactEmailRepository;
    /** @var CorrespondenceAttachments */
    private CorrespondenceAttachments $correspondenceAttachmentsRepository;
    /** @var \Illuminate\Filesystem\FilesystemAdapter */
    private \Illuminate\Filesystem\FilesystemAdapter $coreAdapter;
    /** @var \Illuminate\Filesystem\FilesystemAdapter */
    private \Illuminate\Filesystem\FilesystemAdapter $localAdapter;
    /** @var CorrespondenceResponsesRepository */
    private CorrespondenceResponsesRepository $correspondenceResponsesRepo;
    /** @var CorrespondenceUsersRepository */
    private CorrespondenceUsersRepository $correspondenceUsersRepository;
    /** @var CorrespondenceMessagesRepository */
    private CorrespondenceMessagesRepository $correspondenceMessagesRepository;
    /** @var CorrespondenceEmailsRepository */
    private CorrespondenceEmailsRepository $correspondenceEmailsRepository;
    /** @var CorrespondenceGroupsRepository */
    private CorrespondenceGroupsRepository $correspondenceGroupsRepository;
    private CorrespondenceLookupRepository $correspondenceLookupRepository;
    /* @var AppService */
    private AppService $appService;
    /** @var AuthGroupRepository */
    private AuthGroupRepository $authGroupRepo;
    /** @var AuthGroupService */
    private AuthGroupService $authGroupService;
    private UserService $userService;
    private EmailService $emailService;
    /** @var AuthPermissionRepository */
    private AuthPermissionRepository $authPermissionRepo;
    /**@var SupportTicketLookup */
    private SupportTicketLookup $supportTicketLookupRepo;
    /**@var SupportTicketMessage */
    private SupportTicketMessage $supportTicketMessageService;

    /**
     * CorrespondenceService constructor.
     * @param CorrespondenceRepository $correspondenceRepository
     * @param UserContactEmail $contactEmailRepository
     * @param CorrespondenceAttachments $correspondenceAttachmentsRepository
     * @param CorrespondenceResponsesRepository $correspondenceResponsesRepo
     * @param CorrespondenceMessagesRepository $correspondenceMessagesRepository
     * @param CorrespondenceUsersRepository $correspondenceUsersRepository
     * @param CorrespondenceEmailsRepository $correspondenceEmailsRepository
     * @param CorrespondenceGroupsRepository $correspondenceGroupsRepository
     * @param CorrespondenceLookupRepository $correspondenceLookupRepository
     * @param AuthGroupRepository $authGroupRepo
     * @param AppService $appService
     * @param AuthGroupService $authGroupService
     * @param UserService $userService
     * @param EmailService $emailService
     * @param AuthPermissionRepository $authPermissionRepo
     * @param SupportTicketLookup $supportTicketLookupRepo
     * @param SupportTicketMessage $supportTicketMessageService
     */
    public function __construct(
        CorrespondenceRepository $correspondenceRepository,
        UserContactEmail $contactEmailRepository,
        CorrespondenceAttachments $correspondenceAttachmentsRepository,
        CorrespondenceResponsesRepository $correspondenceResponsesRepo,
        CorrespondenceMessagesRepository $correspondenceMessagesRepository,
        CorrespondenceUsersRepository $correspondenceUsersRepository,
        CorrespondenceEmailsRepository $correspondenceEmailsRepository,
        CorrespondenceGroupsRepository $correspondenceGroupsRepository,
        CorrespondenceLookupRepository $correspondenceLookupRepository,
        AuthGroupRepository $authGroupRepo,
        AppService $appService,
        AuthGroupService $authGroupService,
        UserService $userService,
        EmailService $emailService,
        AuthPermissionRepository $authPermissionRepo,
        SupportTicketLookup $supportTicketLookupRepo,
        SupportTicketMessage $supportTicketMessageService
    ) {
        $this->authGroupRepo = $authGroupRepo;
        $this->contactEmailRepository = $contactEmailRepository;
        $this->correspondenceRepository = $correspondenceRepository;
        $this->correspondenceResponsesRepo = $correspondenceResponsesRepo;
        $this->correspondenceAttachmentsRepository = $correspondenceAttachmentsRepository;
        $this->correspondenceMessagesRepository = $correspondenceMessagesRepository;
        $this->correspondenceUsersRepository = $correspondenceUsersRepository;
        $this->correspondenceEmailsRepository = $correspondenceEmailsRepository;
        $this->correspondenceGroupsRepository = $correspondenceGroupsRepository;
        $this->correspondenceLookupRepository = $correspondenceLookupRepository;
        $this->appService = $appService;
        $this->authGroupService = $authGroupService;
        $this->userService = $userService;
        $this->emailService = $emailService;
        $this->authPermissionRepo = $authPermissionRepo;
        $this->supportTicketLookupRepo = $supportTicketLookupRepo;
        $this->supportTicketMessageService = $supportTicketMessageService;
        $this->initFileSystemAdapter();
    }

    /**
     *
     */
    private function initFileSystemAdapter()
    {
        if (env("APP_ENV") == "local") {
            $this->coreAdapter = Storage::disk("local");
        } else {
            $this->coreAdapter = bucket_storage("soundblock");
        }

        $this->localAdapter = Storage::disk("local");
    }

    public function findAllWithAllowedApps(int $per_page, array $arrFilters, array $arrAllowedApps)
    {
        [$objCorrespondence, $availableMetaData] = $this->correspondenceRepository->findAllWithAllowedApps($per_page, $arrFilters, $arrAllowedApps);

        return ([$objCorrespondence, $availableMetaData]);
    }

    /**
     * @param array $requestData
     * @param string $clientIp
     * @param string $clientHost
     * @param array $attachments
     * @return mixed
     * @throws \Exception
     */
    public function create(array $requestData, string $clientIp, string $clientHost, string $clientAgent, array $attachments)
    {
        /* Creating Insert Data Array */
        $arrInsertData = [];
        $objApp = isset($requestData["from"]) ? $this->appService->find($requestData["from"]) : Client::app();
        $arrInsertData["app_id"] = $objApp->app_id;
        $arrInsertData["app_uuid"] = $objApp->app_uuid;
        $arrInsertData["email_subject"] = $requestData["subject"];
        $arrInsertData["correspondence_uuid"] = Util::uuid();

        /* Insert Data */
        $objCorrespondence = $this->correspondenceRepository->create($arrInsertData);

        $arrMessageData["remote_addr"] = $clientIp;
        $arrMessageData["remote_host"] = $clientHost;
        $arrMessageData["remote_agent"] = $clientAgent;
        $arrMessageData["email"] = $requestData["email"];

        if (isset($requestData["json"])) {
            $arrMessageData["email_json"] = $requestData["json"];
        } else {
            if (isset($requestData["text"])) {
                $arrMessageData["email_text"] = $requestData["text"];
            }
        }

        $objMessage = $this->storeMessage($objCorrespondence, $arrMessageData, $attachments);

        /*  Store Emails */
        $arrEmails = $requestData["emails"] ?? null;
        if (!is_null($arrEmails)) {
            $this->storeCorrespondenceEmail($objCorrespondence, $arrEmails);
        }

        /*  Store Groups */
        $arrGroups = $requestData["groups"] ?? null;
        if (!is_null($arrGroups)) {
            $this->storeCorrespondenceGroup($objCorrespondence, $arrGroups);
        }

        /*  Store Users */
        $arrUsers = $requestData["users"] ?? null;
        if (!is_null($arrUsers)) {
            $this->storeCorrespondenceUsers($objCorrespondence, $arrUsers);
        }

        $strConfirmationPage = $requestData["confirmation_page"] . "?uuid={$objCorrespondence->correspondence_uuid}&email={$objCorrespondence->email}";

        /* Send Mail and Notifications */
        Mail::to($objCorrespondence->email)->send(new CorrespondenceConfirmationMail(
            $objCorrespondence->app,
            $objCorrespondence,
            $strConfirmationPage
        ));
        $this->notifyOtherCorrespondencemembers($objCorrespondence, $objMessage, $objCorrespondence->email);
        $this->notifyAdmins($objCorrespondence, $objApp, $objMessage);
        $this->storeCorrespondenceGroup($objCorrespondence, ["Arena.Support"]);

        $arrResponse = $objCorrespondence->toArray();
        $arrResponse["messages"] = CorrespondenceMessageResource::collection($objCorrespondence->messages);
        $arrResponse["email"] = $objCorrespondence->email;
        $arrResponse["confirmation_page"] = $strConfirmationPage;
        unset($arrResponse["app"]);

        return ($arrResponse);
    }

    public function getCorrespondence(string $strUuid, string $strEmail = null)
    {
        $objCorrespondence = $this->correspondenceRepository->find($strUuid);
        if (!$objCorrespondence) {
            throw new Exception("User Correspondence not found", 403);
        }
        if ($strEmail) {
            if ($objCorrespondence->email != $strEmail) {
                throw new Exception("User Correspondence not found", 403);
            }
        }

        return $objCorrespondence;
    }

    private function notifyAdmins($objCorrespondence, $objApp, $objMessage)
    {
        $arrPermissionsApps = config("constant.support.apps_types_permissions");
        $strPermission = $arrPermissionsApps[strtolower($objApp->app_name)];
        $objUsers = Util::getUsersByPermissionAndGroup("Arena.Support", $strPermission);

        foreach ($objUsers as $objUser) {
            $strEmail = $objUser->primary_email->user_auth_email;

            if (config("app.env") != "prod" && $strEmail == "devans@arena.com") {
                continue;
            }

            Mail::to($strEmail)->send(new CorrespondenceMail($objCorrespondence, $objMessage));
        }

        /* Send Notification */
        $strMemo = "&quot;Correspondence&quot; by {$objCorrespondence->email}&bull;";

        notify_group_permission("Arena.Support", $strPermission, $objApp, "New Correspondence", $strMemo, Builder::notification_link([
            "link_name" => "Check Correspondence",
            "url"       => app_url("office") . "customers/correspondence/" . $objCorrespondence->correspondence_uuid,
        ]));

        return null;
    }

    public function ingest(array $arrPayload, $strClientHost, $strClientIp, $strClientAgent, $arrAttachments)
    {
        //-- Preprare Data for Usage
        $strBodyHtml = $arrPayload["stripped-html"] ?? $arrPayload["body-html"];
        $strBodyPlain = $arrPayload["stripped-text"] ?? $arrPayload["body-plain"];
        $strSubject = $arrPayload["subject"];
        $strFromEMail = trim(explode("<", $arrPayload["from"])[1], ">");
        $strRecipent     = $arrPayload["recipient"];
        $headers = json_decode($arrPayload["message-headers"], true);
        $arrHeaders = [];
        foreach ($headers as $header) {
            $arrHeaders[$header[0]] = $header[1];
        }

        $strClientIp = $arrHeaders["X-Originating-Ip"] ?? $strClientIp;
        $strEmailId = $arrHeaders["Message-Id"];
        $strClientAgent = $arrHeaders["User-Agent"] ?? $strClientAgent;
        $strParentEmailId = $arrHeaders["In-Reply-To"] ?? null;

        //-- Check if Mail is Relating To Support System.

        $isSupportTicketMail = $this->supportTicketLookupRepo->findByRef($strParentEmailId);
        if ($isSupportTicketMail) {
            $supportTicket = $isSupportTicketMail->ticket;
            $arrSupportTicketData = [
                "ticket"          => $supportTicket->ticket_uuid,
                "user"            => $supportTicket->user_uuid,
                "message_text"    => $strBodyPlain,
                "flag_officeonly" => false,
                "files"           => $arrAttachments,
            ];
            return DB::transaction(function () use ($arrSupportTicketData, $supportTicket, $strEmailId) {
                $this->supportTicketLookupRepo->insert($supportTicket, $strEmailId);
                return $this->supportTicketMessageService->ingestMessage($arrSupportTicketData);
            });
        }

        // Declare Empty Data For a new Message
        $arrMessageData = [];
        //Determine App
        $objAllApps = $this->appService->findAll();
        $objApp = $this->appService->findOneByName("arena");
        foreach ($objAllApps as $app) {
            if (str_contains(strtolower(str_replace("@support.arena.com", "", $strRecipent)), $app->app_name)) {
                $objApp = $app;
                break;
            }
        }
        //-- Determine if its a new Correspondence
        //-- Discard Correspondence on Develop when user is starting a new Correspondence from Mail
        if (!$strParentEmailId && env("APP_ENV") !== "production") {
            return;
        }
        $objCorrespondenceLookup = $this->correspondenceLookupRepository->findByref($strParentEmailId);
        if ($strParentEmailId && $objCorrespondenceLookup) {
            $objCorrespondence = $this->correspondenceRepository->findByUuid($objCorrespondenceLookup->correspondence_uuid);
        } else if (env("APP_ENV") === "production") {
            $arrCorrespondenceData = [
                "app_id" => $objApp->app_id,
                "app_uuid" => $objApp->app_uuid,
                "email_subject" => $strSubject,
                "correspondence_uuid" => Util::uuid()
            ];
            $objCorrespondence  = $this->correspondenceRepository->create($arrCorrespondenceData);
        } else {
            return;
        }

        $arrMessageData["remote_addr"] = $strClientIp;
        $arrMessageData["remote_host"] = $strClientHost;
        $arrMessageData["remote_agent"] = $strClientAgent;
        $arrMessageData["email"] = $strFromEMail;
        $arrMessageData["email_html"] = $strBodyHtml;
        $arrMessageData["email_text"] = $strBodyPlain;
        // $arrMessageData["email_id"] = $strEmailId;
        // $arrMessageData["email_parent_id"] = $strParentEmailId;

        $objMessage = $this->storeMessage($objCorrespondence, $arrMessageData, $arrAttachments);
        $this->storeCorrespondenceLookup($objCorrespondence, $strEmailId);
        //-- Notify Other Members on the Correspondence
        $this->notifyOtherCorrespondencemembers($objCorrespondence, $objMessage, $strFromEMail);
        $this->notifyAdmins($objCorrespondence, $objApp, $objMessage);
        return $objMessage;
    }

    public function notifyOtherCorrespondencemembers($objCorrespondence, $objMessage, $strMainSenderEmail)
    {
        $arrCorrespondenceMembersEmail = $this->extractAllCorrespondenceEmails($objCorrespondence);
        $intEmailIndex = array_search($strMainSenderEmail, $arrCorrespondenceMembersEmail);
        array_splice($arrCorrespondenceMembersEmail, $intEmailIndex, 1);
        foreach ($arrCorrespondenceMembersEmail as $strEmail) {
            if (filter_var($strEmail, FILTER_VALIDATE_EMAIL)) {
                Mail::to($strEmail)
                    ->send(new CorrespondenceResponseMail($objCorrespondence->app, $objMessage));
            }
        }
        return $arrCorrespondenceMembersEmail;
    }

    public function substractEmail(array $arrNeedle, array $arrHaystack): array
    {
        foreach ($arrNeedle as $strEmail) {
            $intEmailIndex = array_search($strEmail, $arrNeedle);
            if ($intEmailIndex !== false) {
                array_splice($arrHaystack, $intEmailIndex, 1);
            }
        }
        return $arrHaystack;
    }

    public function storeCorrespondenceLookup($objCorrespondence, $strLookupRef)
    {
        return $this->correspondenceLookupRepository->create([
            "row_uuid" => Util::uuid(),
            "correspondence_id" => $objCorrespondence->correspondence_id,
            "correspondence_uuid" => $objCorrespondence->correspondence_uuid,
            "lookup_email_ref" => $strLookupRef
        ]);
    }

    public function storeMessage($objCorrespondence, $arrInsertData, $attachments): CorrespondenceMessage
    {
        $strEmail = $arrInsertData["email"] ?? "";
        $objContactEmail = $this->contactEmailRepository->find($strEmail);
        if (!is_null($objContactEmail)) {
            $arrInsertData["user_id"] = $objContactEmail->user_id;
            $arrInsertData["user_uuid"] = $objContactEmail->user_uuid;
        }
        $arrInsertData["user_email"] = $arrInsertData["email"];
        unset($arrInsertData["email"]);
        $arrInsertData["correspondence_id"] = $objCorrespondence->correspondence_id;
        $arrInsertData["correspondence_uuid"] = $objCorrespondence->correspondence_uuid;
        $objMessage = $this->correspondenceMessagesRepository->create($arrInsertData);

        if (!empty($attachments)) {
            /* Insert Attachments in table */
            $path = "correspondence/{$objCorrespondence->correspondence_uuid}/{$objMessage->message_uuid}";
            foreach ($attachments as $attachment) {
                $strOriginalFileName = pathinfo($attachment->getClientOriginalName())["filename"];
                $strFileName = mt_rand() . "-" . Str::slug($strOriginalFileName) . "." . $attachment->getClientOriginalExtension();
                // /public/correspondence/correspondence_uuid/message_uuid
                $strFilePath = "public/" . $path;
                if (bucket_storage("core")->putFileAs($strFilePath, $attachment, $strFileName, ["visibility" => "public"])) {
                    $arrFileData["attachment_name"] = $strFileName;
                    $arrFileData["attachment_url"] = cloud_url("core") . "{$path}/" . $strFileName;
                    $arrFileData["correspondence_id"] = $objCorrespondence->correspondence_id;
                    $arrFileData["correspondence_uuid"] = $objCorrespondence->correspondence_uuid;
                    $arrFileData["message_id"] = $objMessage->message_id;
                    $arrFileData["message_uuid"] = $objMessage->message_uuid;

                    $this->correspondenceAttachmentsRepository->create($arrFileData);
                }
            }
        }

        return $objMessage;
    }

    public function attachmentsToZip(array $attachments, $objCorrespondence)
    {
        $zip = new ZipArchive();

        /* Create path for .zip archive */
        $localAdapterPath = "public/" . Merch::correspondence_path($objCorrespondence);
        $zipPath = $localAdapterPath . DIRECTORY_SEPARATOR . $objCorrespondence->correspondence_uuid . ".zip";

        if ($this->coreAdapter->exists($zipPath)) {
            $this->coreAdapter->delete($zipPath);
        }

        if (!$this->localAdapter->exists($localAdapterPath)) {
            $this->localAdapter->makeDirectory($localAdapterPath);
        }

        /* Create .zip file and put to core bucket */
        if ($zip->open($this->localAdapter->path($zipPath), ZipArchive::CREATE)) {
            foreach ($attachments as $file) {
                $zip->addFile($file->getPathName(), $file->getClientOriginalName());
            }

            $boolRes = $zip->close();

            if ($boolRes === true) {
                $readStream = $this->localAdapter->readStream($zipPath);
                $this->coreAdapter->writeStream($zipPath, $readStream);
                $this->coreAdapter->setVisibility($zipPath, "public");
                $this->localAdapter->delete($zipPath);

                return (true);
            }

            return (false);
        }

        return (false);
    }

    /**
     * @param string $correspondence
     * @param array $arrParams
     * @return bool
     * @throws \Exception
     */
    public function responseForCorrespondence(string $correspondence, array $arrParams, $strClientAgent, $strClientHost, $strClientIp)
    {
        $objCorrespondence = $this->correspondenceRepository->find($correspondence);
        $objAdminUser  = auth()->user();
        $strAdminEmail = is_null($objAdminUser->primary_email) ? null : $objAdminUser->primary_email->user_auth_email;
        if (!$objCorrespondence) {
            throw new Exception("Correspondence Not Found");
        }
        $arrMessageData = [];
        $arrMessageData["remote_addr"] = $strClientIp;
        $arrMessageData["remote_host"] = $strClientHost;
        $arrMessageData["remote_agent"] = $strClientAgent;
        $arrMessageData["email"] = $strAdminEmail;
        $arrMessageData["email_text"] = $arrParams["text"];

        $objMessage = $this->storeMessage($objCorrespondence, $arrMessageData, $arrParams["attachments"]);
        $arrMemberEmails = $this->extractAllCorrespondenceEmails($objCorrespondence);
        foreach ($arrMemberEmails as $strEmail) {
            if (filter_var($strEmail, FILTER_VALIDATE_EMAIL)) {
                Mail::to($strEmail)
                    ->send(new CorrespondenceResponseMail($objCorrespondence->app, $objMessage));
            }
        }

        return $objMessage;
    }

    public function storeCorrespondenceGroup($objCorrespondence, $arrGroupsUuid)
    {
        $arrOutput = [];
        foreach ($arrGroupsUuid as $strGroupUuid) {
            if (gettype($strGroupUuid) != 'string') {
                continue;
            }
            $objCorrespondenceGroup = $this->correspondenceGroupsRepository->findByCorrespondenceAndGroupUuid($objCorrespondence->correspondence_uuid, $strGroupUuid);
            if (!$objCorrespondenceGroup) {
                $objGroup = $this->authGroupService->find($strGroupUuid, false);
                if (is_null($objGroup)) {
                    continue;
                }
                $this->correspondenceGroupsRepository->create([
                    "row_uuid" => Util::uuid(),
                    "correspondence_id" => $objCorrespondence->correspondence_id,
                    "correspondence_uuid" => $objCorrespondence->correspondence_uuid,
                    "group_id" => $objGroup->group_id,
                    "group_uuid" => $objGroup->group_uuid,
                ]);
            } else {
                $objCorrespondenceGroup->restore();
                $objGroup = $this->authGroupService->find($strGroupUuid, false);
            }
            if (!is_null($objGroup)) {
                array_push($arrOutput, [
                    "group_uuid" => $objGroup->group_uuid,
                    "group_name" => $objGroup->group_name
                ]);
            }
        }

        return $arrOutput;
    }

    public function storeCorrespondenceUsers($objCorrespondence, $arrUsersUuid)
    {
        $arrOutput = [];
        foreach ($arrUsersUuid as $strUserUuid) {
            if (gettype($strUserUuid) != 'string') {
                continue;
            }
            $objCorrespondenceUser = $this->correspondenceUsersRepository->findByCorrespondenceAndUserUuid($objCorrespondence->correspondence_uuid, $strUserUuid);
            if (!$objCorrespondenceUser) {
                $objUser = $this->userService->find($strUserUuid);
                $this->correspondenceUsersRepository->create([
                    "row_uuid" => Util::uuid(),
                    "user_id" => $objUser->user_id,
                    "user_uuid" => $objUser->user_uuid,
                    "correspondence_id" => $objCorrespondence->correspondence_id,
                    "correspondence_uuid" => $objCorrespondence->correspondence_uuid
                ]);
            } else {
                $objCorrespondenceUser->restore();
                $objUser = $this->userService->find($objCorrespondenceUser->user_uuid);
            }
            if (!is_null($objUser)) {
                array_push($arrOutput, [
                    "user_uuid" => $objUser->user_uuid,
                    "user_name" => $objUser->name,
                    "image_url" => $objUser->avatar,
                ]);
            }
        }

        return $arrOutput;
    }

    public function storeCorrespondenceEmail($objCorrespondence, $arrEmails)
    {
        $arrOutput = [
            "emails" => [],
            "users" => [],
        ];
        foreach ($arrEmails as $strEmail) {
            if (gettype($strEmail) != 'string') {
                continue;
            }
            $objCorrespondenceEmail = $this->correspondenceEmailsRepository->findByCorrespendenceUuidAndEmail($objCorrespondence->correspondence_uuid, $strEmail);
            if (!$objCorrespondenceEmail) {
                //-- Check if email is registered with Us
                $objEmail = $this->emailService->find($strEmail);
                $objUser = !is_null($objEmail) ? $objEmail->user : null;
                if ($objUser) {
                    $this->storeCorrespondenceUsers($objCorrespondence, [$objUser->user_uuid]);
                    array_push($arrOutput["users"], [
                        "user_uuid" => $objUser->user_uuid,
                        "user_name" => $objUser->name,
                        "image_url" => $objUser->avatar,
                        "email" => $strEmail
                    ]);
                } else {
                    $this->correspondenceEmailsRepository->create([
                        "row_uuid" => Util::uuid(),
                        "correspondence_uuid" => $objCorrespondence->correspondence_uuid,
                        "correspondence_id" => $objCorrespondence->correspondence_id,
                        "email_address" => $strEmail
                    ]);
                    array_push($arrOutput["emails"], $strEmail);
                }
            } else {
                $objCorrespondenceEmail->restore();
                array_push($arrOutput["emails"], $strEmail);
            }
        }
        return $arrOutput;
    }


    public function removeCorrespondenceGroup($objCorrespondence, $arrGroupsUuid)
    {
        foreach ($arrGroupsUuid as $strGroupUuid) {
            if (gettype($strGroupUuid) != 'string') {
                continue;
            }
            $objCorrespondenceGroup = $this->correspondenceGroupsRepository->findByCorrespondenceAndGroupUuid($objCorrespondence->correspondence_uuid, $strGroupUuid);
            if ($objCorrespondenceGroup) {
                $objCorrespondenceGroup->delete();
            }
        }
    }

    public function removeCorrespondenceEmail($objCorrespondence, $arrEmails)
    {
        foreach ($arrEmails as $strEmail) {
            if (gettype($strEmail) != 'string') {
                continue;
            }
            $objCorrespondenceEmail = $this->correspondenceEmailsRepository->findByCorrespendenceUuidAndEmail($objCorrespondence->correspondence_uuid, $strEmail);
            if ($objCorrespondenceEmail) {
                $objCorrespondenceEmail->delete();
            }
        }
    }

    public function removeCorrespondenceUsers($objCorrespondence, $arrUsers)
    {
        foreach ($arrUsers as $strUserUuid) {
            if (gettype($strUserUuid) != 'string') {
                continue;
            }
            $objCorrespondenceUser = $this->correspondenceUsersRepository->findByCorrespondenceAndUserUuid($objCorrespondence->correspondence_uuid, $strUserUuid);
            if ($objCorrespondenceUser) {
                $objCorrespondenceUser->delete();
            } else {
                throw new Exception("Correspondence user not found");
            }
        }
        return true;
    }

    public function extractAllCorrespondenceEmails($objCorrespondence)
    {
        $arrOutput = [];
        //-- Add Main Correspondence user Email
        array_push($arrOutput, $objCorrespondence->email);
        //--Add ObjCorrespondenceEmails
        $objCorrespondenceEmails = $this->correspondenceEmailsRepository->findByCorrespondenceUuid($objCorrespondence->correspondence_uuid);
        foreach ($objCorrespondenceEmails as $objCorrespondenceEmail) {
            if (!in_array($objCorrespondenceEmail->email_address, $arrOutput)) {
                array_push($arrOutput, $objCorrespondenceEmail->email_address);
            }
        }
        //-- Add ObjCorrespendence Users
        $objCorrespondenceUsers = $this->correspondenceUsersRepository->findByCorrespondenceUuid($objCorrespondence->correspondence_uuid);
        foreach ($objCorrespondenceUsers as $objCorrespondenceUser) {
            $objUser = $this->userService->find($objCorrespondenceUser->user_uuid);
            if ($objUser && $objUser->primary_email) {
                if (!in_array($objUser->primary_email->user_auth_email, $arrOutput)) {
                    array_push($arrOutput, $objUser->primary_email->user_auth_email);
                }
            }
        }
        //-- Add Email From Groups -
        $objCorrespondenceGroups = $this->correspondenceGroupsRepository->findByCorrespondenceUuid($objCorrespondence->correspondence_uuid);
        foreach ($objCorrespondenceGroups as $objCorrespondenceGroup) {
            $objGroup = $this->authGroupService->find($objCorrespondenceGroup->group_uuid, false);
            if ($objGroup) {
                $objGroupUsers = $objGroup->users;
                foreach ($objGroupUsers as $objGroupUser) {
                    if ($objGroupUser->primary_email && !in_array($objGroupUser->primary_email->user_auth_email, $arrOutput)) {
                        array_push($arrOutput, $objGroupUser->primary_email->user_auth_email);
                    }
                }
            }
        }

        return $arrOutput;
    }

    /**
     * @param string $strEmail
     * @param string $strSubject
     * @param string $strJson
     * @return mixed
     */
    public function checkDuplicate(string $strEmail, string $strSubject, string $strJson, $columnType = "json")
    {
        return $this->correspondenceRepository->checkDuplicate($strEmail, $strSubject, $strJson, $columnType);
    }
    /**
     * @param String $correspondenceUuid
     */
    public function getCorrespondenceMessages(String $strCorrespondenceUuid, Int $perPage)
    {
        $objMessages = $this->correspondenceMessagesRepository->findByCorrespondenceUuid($strCorrespondenceUuid, $perPage);
        $arrMessages = collect($objMessages)->toArray();
        $availableMetaData = Arr::only($arrMessages, ["current_page", "last_page", "next_page_url", "per_page", "total"]);
        $arrData = $arrMessages["data"];
        return [$arrData, $availableMetaData];
    }

    public function deleteCorrespondence(string $correspondenceUuid)
    {
        $objCorrespondence = $this->correspondenceRepository->find($correspondenceUuid);
        if (!$objCorrespondence) {
            throw new Exception("Correspondence not found", 404);
        }
        /** Delete Messages */
        $objCorrespondence->messages->each(function ($message) {
            /**Delete Message Atachment Record */
            if (!empty($message->files)) {
                $message->files->each(function ($attachment) {
                    $attachment->delete();
                });
            }

            $message->delete();
        });
        /** Delete Record From Correspondence Users */
        $objCorrespondenceUsers = $this->correspondenceUsersRepository->findByCorrespondenceUuid($objCorrespondence->correspondence_uuid);
        if ($objCorrespondenceUsers) {
            $objCorrespondenceUsers->each(function ($objUser) {
                $objUser->delete();
            });
        }

        /**Delete Record From Correspondence Emails */
        $objCorrespondenceEmails = $this->correspondenceEmailsRepository->findByCorrespondenceUuid($objCorrespondence->correspondence_uuid);
        if ($objCorrespondenceEmails) {
            $objCorrespondenceEmails->each(function ($objEmail) {
                $objEmail->delete();
            });
        }

        /** Delete the Correspondence Itself */
        $objCorrespondence->delete();
        return true;
    }

    public function combineAttachments($arrFiles)
    {
        $output = [];
        foreach ($arrFiles as $file) {
            if (is_array($file)) {
                foreach ($file as $index => $value) {
                    array_push($output, $value);
                }
            } else {
                array_push($output, $file);
            }
        }
        return $output;
    }



    public static function verifyWebhookSignature($token, $timestamp, $signature)
    {
        // check if the timestamp is fresh
        if (\abs(\time() - $timestamp) > 15) {
            return false;
        }

        // returns true if signature is valid
        return \hash_equals(\hash_hmac("sha256", $timestamp . $token, env("MAILGUN_SECRET")), $signature);
    }
}
