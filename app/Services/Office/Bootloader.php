<?php

namespace App\Services\Office;

use App\Models\Users\User as UserModel;
use App\Services\Core\Auth\AuthGroup as AuthGroupService;
use App\Services\Core\Auth\AuthPermission as AuthPermissionService;
use App\Repositories\{
    Soundblock\Data\ProjectsRoles as ProjectsRolesRepository,
    Soundblock\Platform as PlatformRepository
};

class Bootloader {
    /** @var ProjectsRolesRepository */
    private ProjectsRolesRepository $projectsRolesRepo;
    /** @var AuthPermissionService */
    private AuthPermissionService $authPermissionService;
    /** @var AuthGroupService */
    private AuthGroupService $authGroupSerice;
    /** @var PlatformRepository */
    private PlatformRepository $platformRepo;

    /**
     * @param ProjectsRolesRepository $projectsRolesRepo
     * @param AuthPermissionService $authPermissionService
     * @param AuthGroupService $authGroupSerice
     * @param PlatformRepository $platformRepo
     */
    public function __construct(ProjectsRolesRepository $projectsRolesRepo, AuthPermissionService $authPermissionService,
                                AuthGroupService $authGroupSerice, PlatformRepository $platformRepo) {
        $this->projectsRolesRepo = $projectsRolesRepo;
        $this->authPermissionService = $authPermissionService;
        $this->authGroupSerice = $authGroupSerice;
        $this->platformRepo = $platformRepo;
    }

    public function prepareDataForBootloader(UserModel $objUser){
        $arrGroups = ["Arena.Superusers", "Arena.Office", "Arena.Support"];
        $returnData = [];
        $userPermissions = [];

        foreach ($arrGroups as $strGroup) {
            $objGroup = $this->authGroupSerice->findByName($strGroup);
            $arrayGroupPermissions = $this->authPermissionService->findAllUserPermissionsByGroup($objGroup, $objUser)->toArray();

            if (!empty($arrayGroupPermissions)) {
                $userPermissions[$strGroup] = [];
                foreach ($arrayGroupPermissions as $arrayGroupPermission) {
                    $userPermissions[$strGroup] += [$arrayGroupPermission["permission_name"] => $arrayGroupPermission["permission_value"]];
                }
            }
        }

        $returnData["project_roles"] = $this->projectsRolesRepo->all();
        $returnData["permissions"] = $userPermissions;
        $returnData["platforms"] = $this->platformRepo->findAll();

        return ($returnData);
    }
}
