<?php

namespace App\Http\Controllers\Soundblock;

use Util;
use Auth;
use Client;
use Exception;
use App\Services\{
    Soundblock\Project,
    Common\Common,
    Common\Notification,
    Core\Auth\AuthGroup,
    Core\Auth\AuthPermission,
    Soundblock\Data as DataService,
    Soundblock\Platform as PlatformService,
    Office\SupportTicketMessage as SupportTicketMessageService,
    Soundblock\Payment\MusicPaymentBalance as MusicPaymentBalanceService
};
use App\Models\Users\User;
use App\Http\Controllers\Controller;
use App\Contracts\Soundblock\Contracts\SmartContracts;

/**
 * @group Soundblock
 *
 * Soundblock routes
 */
class BootLoader extends Controller {
    /** @var AuthPermission $authPermService */
    protected AuthPermission $authPermService;
    /** @var AuthGroup $authGroupService */
    protected AuthGroup $authGroupService;
    /** @var Common $commonService */
    protected Common $commonService;
    /** @var DataService */
    private DataService $dataService;
    /** @var PlatformService */
    private PlatformService $platformService;
    /** @var Project */
    private Project $projectService;
    /** @var SupportTicketMessageService */
    private SupportTicketMessageService $supportTicketMessageService;
    /**
     * @var MusicPaymentBalanceService
     */
    private MusicPaymentBalanceService $musicPaymentBalanceService;

    /**
     * @param AuthPermission $authPermService
     * @param AuthGroup $authGroupService
     * @param Common $commonService
     * @param DataService $dataService
     * @param PlatformService $platformService
     * @param Project $projectService
     * @param SupportTicketMessageService $supportTicketMessageService
     * @param MusicPaymentBalanceService $musicPaymentBalanceService
     */
    public function __construct(AuthPermission $authPermService, AuthGroup $authGroupService, Common $commonService,
                                DataService $dataService, PlatformService $platformService, Project $projectService,
                                SupportTicketMessageService $supportTicketMessageService,
                                MusicPaymentBalanceService $musicPaymentBalanceService) {
        $this->authPermService = $authPermService;
        $this->authGroupService = $authGroupService;
        $this->commonService = $commonService;
        $this->dataService = $dataService;
        $this->platformService = $platformService;
        $this->projectService = $projectService;
        $this->supportTicketMessageService = $supportTicketMessageService;
        $this->musicPaymentBalanceService = $musicPaymentBalanceService;
    }

    /**
     * @param Notification $notificationService
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response|object
     * @throws Exception
     */
    public function index(Notification $notificationService) {
        try {
            /** @var User $user */
            $user = Auth::user();
            $user->update(["last_login" => now()]);
            $arrAccounts = $this->commonService->findByUser($user);

            $accounts = collect();

            foreach ($arrAccounts as $objAccount) {
                $permissions = $this->authPermService->findUserPermissionByAccount($objAccount, $user);
                $arrAccount = $objAccount->toArray();
                $arrAccount["plan_name"] = optional(optional($objAccount->activePlan)->planType)->plan_name;
                $accounts->push([
                    "account"     => $arrAccount,
                    "permissions" => $permissions,
                ]);
            }

            $permittedServices = $this->commonService->findByUserWithCreatingPermission($user);

            $data = $user->makeHidden("groups")->load(["notificationSettings" => function ($query) {
                $query->where("notifications_users_settings.app_id", Client::app()->app_id);
            }]);

            $notificationSetting = $user->notificationSettings->first();
            unset($data->notificationSettings);
            $data->notification_setting = $notificationSetting;
            $data->unread_notifications_count = $notificationService->findAllByStatus("unread")->count();
            $data->avatar = Util::avatar_url($user);
            [$invitedServices, $emailInvitations] = $this->commonService->getAccountInvites($user);
            $unreadUserMessages = $this->supportTicketMessageService->getUserUnreadMessages($user)->count();
            [$floatUserBalance, $arrUserEarningsHistory] = $this->musicPaymentBalanceService->getDataForBootloader($user);

            return ($this->apiReply([
                "user"               => $data,
                "accounts"           => $accounts,
                "permitted_accounts" => $permittedServices,
                "invited_accounts"   => $invitedServices,
                "invited_emails"     => $emailInvitations,
                "unread_messages"    => $unreadUserMessages,
                "user_balance"       => $floatUserBalance,
                "user_earnings"      => $arrUserEarningsHistory,
            ]));
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @return \Illuminate\Http\Response|object
     */
    public function getData(){
        $objLanguages = $this->dataService->getAllLanguages();
        $objContributors = $this->dataService->getContributors();
        $objGenres = $this->dataService->getGenres([]);
        $objPlatforms = $this->platformService->findAllWithoutPaginate();
        $objProjectFormats = $this->projectService->getFormats();
        $objProjectRoles = $this->projectService->getRoles();

        return (
            $this->apiReply([
                "languages" => $objLanguages,
                "contributor_types" => $objContributors,
                "genres" => $objGenres,
                "platforms" => $objPlatforms,
                "project_formats" => $objProjectFormats,
                "project_roles" => $objProjectRoles
            ])
        );
    }

    /**
     * @param SmartContracts $smartContract
     * @param Project $projectService
     * @param Notification $notificationService
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response|object
     * @throws Exception
     */
    public function getNoteableEvents(SmartContracts $smartContract, Project $projectService, Notification $notificationService) {
        try {
            $response = [];
            $response["contract"] = $smartContract->getLatestByProjectsAndStatus($projectService->__getAllByUser(), ["Awaiting Registration", "penDing"]);
            $response["deploy"] = $projectService->__getAllByDeploymentStatus("deployed");
            // Notifications to need the response.
            $response["notifications"] = $notificationService->findAllByStatus("unread");

            return ($this->apiReply($response));
        } catch (Exception $e) {
            throw $e;
        }
    }
}
