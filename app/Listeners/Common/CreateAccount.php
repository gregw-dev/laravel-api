<?php

namespace App\Listeners\Common;

use Util;
use App\Events\Common\CreateAccount as CreateServiceEvent;
use App\Services\Core\Auth\AuthGroup;
use App\Services\Core\Auth\AuthPermission;
use Constant;
use App\Models\Core\App as AppModel;

class CreateAccount {

    protected AuthGroup $authGroupService;

    protected AuthPermission $authPermService;

    protected $arrAuthGroup;

    /**
     * Create the event listener.
     *
     * @param AuthGroup $authGroupService
     * @param AuthPermission $authPermService
     */
    public function __construct(AuthGroup $authGroupService, AuthPermission $authPermService) {
        $this->authGroupService = $authGroupService;
        $this->authPermService = $authPermService;
    }

    /**
     * Handle the event.
     *
     * @param CreateServiceEvent $event
     * @return void
     */
    public function handle(CreateServiceEvent $event) {
        $this->arrAuthGroup = $event->arrAuthGroup;

        $objApp = AppModel::where("app_name", "soundblock")->first();
        $objAuth = Util::makeAuth($objApp);
        $objAuthGroup = $this->authGroupService->createGroup($this->arrAuthGroup, true, $objApp, $objAuth);

        $allSoundblockAccountPermissions = $this->authPermService->findAllWhere(["App.Soundblock.Account.%"], "name");
//        $soundblockProjectMemberPermissions = $this->authPermService->findAllWhere(["App.Soundblock.Project.Member.%"], "name");

        $this->authGroupService->addUserToGroup($this->arrAuthGroup["user"], $objAuthGroup, $objApp);
//        $arrAccount = Constant::account_level_permissions();

        $this->authPermService->attachGroupPermissions($allSoundblockAccountPermissions, $objAuthGroup);
        $this->authPermService->attachUserPermissions($allSoundblockAccountPermissions, $this->arrAuthGroup["user"], $objAuthGroup);
//        $this->authPermService->attachUserPermissions($soundblockProjectMemberPermissions, $this->arrAuthGroup["user"], $objAuthGroup);
    }
}
