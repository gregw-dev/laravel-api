<?php

namespace App\Console\Commands\Soundblock;

use App\Helpers\Util;
use Illuminate\Console\Command;
use App\Services\Soundblock\Project;
use Illuminate\Support\Facades\DB;
use App\Models\Soundblock\Projects\Project as ProjectModel;
use App\Models\Soundblock\Accounts\Account as AccountModel;
use App\Models\Core\Auth\AuthPermissionsGroup;
use App\Services\Core\Auth\AuthPermission as AuthPermissionService;
use App\Services\Core\Auth\AuthGroup as AuthGroupService;


class AssignMissingAccountAndProjectPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'soundblock:fix_permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to assign missing account and project permissions on Soundblock';

    private Project $projectService;


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Project $projectService)
    {
        parent::__construct();
        $this->projectService = $projectService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        set_time_limit(0);
        return $this->testingForProjectsWithoutATeam();
        // $arrFixSoundblockPermissions = $this->fixSoundblockPermissions();
        // $strResponse = implode(" and ", $arrFixSoundblockPermissions);
        // return $this->info($strResponse);
    }

    public function assignMissingAccountsPermissionsInGroup()
    {
        set_time_limit(0);
        $soundBlockProjectDefaultPermissions = collect(DB::table("core_auth_permissions")->where("permission_name", "like", "%Soundblock.Account%")->get());
        $soundblockAccounts = DB::table("soundblock_accounts")->get();
        $inserted = [];
        if (!$soundblockAccounts) {
            return  "0 new Account permission(s) added successfully";
        }
        $this->withProgressBar($soundblockAccounts, function ($account) use ($soundBlockProjectDefaultPermissions, &$inserted) {
            $accountAuthGroup = DB::table("core_auth_groups")->where("group_name", "App.Soundblock.Account.{$account->account_uuid}")->get();
            if (count($accountAuthGroup) > 1) {
                return ["multiple" => $accountAuthGroup];
            }
            if (count($accountAuthGroup) === 0) {
                $coreAuth = DB::table("core_auth")->where("auth_name", "App.Soundblock")->first();
                $accountAuthGroup = DB::table("core_auth_groups")->insert([
                    "group_uuid" => util::uuid(),
                    "auth_id" => $coreAuth->auth_id,
                    "auth_uuid" => $coreAuth->auth_uuid,
                    "group_name" => "App.Soundblock.Account.{$account->project_uuid}",
                    "group_memo" => "App.Soundblock.Account.( {$account->project_uuid} )",
                    "flag_critical" => 1,
                    "stamp_created_at" => now(),
                    "stamp_created" => time(),
                    "stamp_created_by" => 1,
                    "stamp_updated_at" => now(),
                    "stamp_updated" => time(),
                    "stamp_updated_by" => 1
                ]);
                $accountAuthGroup = DB::table("core_auth_groups")->where("group_name", "App.Soundblock.Account.{$account->project_uuid}")->get();
            }
            $coreAuthPermissions = DB::table("core_auth_permissions_groups")->where("group_uuid", $accountAuthGroup->first()->group_uuid)->get();
            if ($coreAuthPermissions) {
                $defaultPerms = $soundBlockProjectDefaultPermissions->pluck("permission_uuid");
                $difference = $defaultPerms->diff($coreAuthPermissions->pluck("permission_uuid"));
                $differentPermissions = array_values($difference->toArray());
                if (!empty($differentPermissions)) {
                    foreach ($differentPermissions as $permission) {
                        $authPermission = $soundBlockProjectDefaultPermissions
                            ->where("permission_uuid", $permission)->first();
                        $authGroup = $accountAuthGroup->first();
                        $insert = DB::table("core_auth_permissions_groups")->insert([
                            "row_uuid" => Util::uuid(),
                            "group_id" => $authGroup->group_id,
                            "group_uuid" => $authGroup->group_uuid,
                            "permission_id" => $authPermission->permission_id,
                            "permission_uuid" => $authPermission->permission_uuid,
                            "permission_value" => 1,
                            "stamp_created_at" => now(),
                            "stamp_created" => time(),
                            "stamp_created_by" => 1,
                            "stamp_updated_at" => now(),
                            "stamp_updated" => time(),
                            "stamp_updated_by" => 1
                        ]);
                        array_push($inserted, $insert);
                    }
                }
            }
        });

        return count($inserted) . " new Account permission(s) added successfully";
    }

    public function assignMissingProjectsPermissionsInGroup()
    {
        set_time_limit(0);
        $soundBlockProjectDefaultPermissions = collect(DB::table("core_auth_permissions")->where("permission_name", "like", "%Soundblock.Project%")->get());
        $soundblockProjects = DB::table("soundblock_projects")->get();
        $inserted = [];
        if (!$soundblockProjects) {
            return "0 new Project permission(s) added successfully";
        }
        $index = 0;
        $this->withProgressBar($soundblockProjects, function ($project) use ($soundBlockProjectDefaultPermissions, &$inserted, &$index) {
            $index++;
            $accountAuthGroup = DB::table("core_auth_groups")->where("group_name", "App.Soundblock.Project.{$project->project_uuid}")->get();
            $iregular = [];
            if (count($accountAuthGroup) > 1) {
                $irregularData = [$index => (array) $project, "auth_group" => $accountAuthGroup];
                array_push($iregular, $irregularData);
            }
            if (count($accountAuthGroup) === 0) {
                $coreAuth = DB::table("core_auth")->where("auth_name", "App.Soundblock")->first();
                $accountAuthGroup = DB::table("core_auth_groups")->insert([
                    "group_uuid" => util::uuid(),
                    "auth_id" => $coreAuth->auth_id,
                    "auth_uuid" => $coreAuth->auth_uuid,
                    "group_name" => "App.Soundblock.Project.{$project->project_uuid}",
                    "group_memo" => "App.Soundblock.Project.( {$project->project_uuid} )",
                    "flag_critical" => 1,
                    "stamp_created_at" => now(),
                    "stamp_created" => time(),
                    "stamp_created_by" => 1,
                    "stamp_updated_at" => now(),
                    "stamp_updated" => time(),
                    "stamp_updated_by" => 1
                ]);
                $accountAuthGroup = DB::table("core_auth_groups")->where("group_name", "App.Soundblock.Project.{$project->project_uuid}")->get();
            }
            $coreAuthPermissions = DB::table("core_auth_permissions_groups")->where("group_uuid", $accountAuthGroup->first()->group_uuid)->get();
            if ($coreAuthPermissions) {
                $defaultPerms = $soundBlockProjectDefaultPermissions->pluck("permission_uuid");
                $difference = $defaultPerms->diff($coreAuthPermissions->pluck("permission_uuid"));
                $differentPermissions = array_values($difference->toArray());
                if (!empty($differentPermissions)) {
                    foreach ($differentPermissions as $permission) {
                        $authPermission = $soundBlockProjectDefaultPermissions
                            ->where("permission_uuid", $permission)->first();
                        $authGroup = $accountAuthGroup->first();
                        $insert = DB::table("core_auth_permissions_groups")->insert([
                            "row_uuid" => Util::uuid(),
                            "group_id" => $authGroup->group_id,
                            "group_uuid" => $authGroup->group_uuid,
                            "permission_id" => $authPermission->permission_id,
                            "permission_uuid" => $authPermission->permission_uuid,
                            "permission_value" => 0,
                            "stamp_created_at" => now(),
                            "stamp_created" => time(),
                            "stamp_created_by" => 1,
                            "stamp_updated_at" => now(),
                            "stamp_updated" => time(),
                            "stamp_updated_by" => 1
                        ]);
                        array_push($inserted, $insert);
                    }
                }
            }
        });

        return count($inserted) . " new Project permission(s) added successfully";
    }

    public function setAllSoundblockPermissionGroupsToFalse()
    {

        $whitelist = ["App.Soundblock.Project", "App.Soundblock.Account"];
        $allPermissionGroups = AuthPermissionsGroup::query()
            ->with("authGroup")->get(["group_uuid", "permission_value"]);
        $countEdited = 0;
        $this->withProgressBar($allPermissionGroups, function ($objPermissionGroup) use (&$countEdited, $whitelist) {
            $coreAuthGroup = $objPermissionGroup->authGroup;

            if ($coreAuthGroup) {
                $proceed = false;
                foreach ($whitelist as $item) {
                    if (str_contains($coreAuthGroup->group_name, $item)) {
                        $proceed = true;
                        break;
                    }
                }

                if ($proceed && $objPermissionGroup->permission_value == 1) {
                    DB::table("core_auth_permissions_groups")->where("group_uuid", $objPermissionGroup->group_uuid)
                        ->update(["permission_value" => 0]);
                    $countEdited++;
                }
            }
        });

        return $countEdited .  " Permission(s) Groups set to false ";
    }


    public function setProjectAndAccountUsersPermissions()
    {
        set_time_limit(0);
        $objAllProject = ProjectModel::all();
        $intCount = 0;
        $objSoundBlockDefaultPermissions = $this->getDefaultSoundBlockPermissions();
        $this->withProgressBar($objAllProject, function ($objProject) use (&$intCount, &$objSoundBlockDefaultPermissions) {
            $projectTeam = $this->projectService->teamService->getUsers($objProject);
            dd($objSoundBlockDefaultPermissions);
            if ($projectTeam) {
                $projectTeamUsers = $projectTeam->users;
                if ($projectTeamUsers) {
                    foreach ($projectTeamUsers as $arrUser) {
                        $strRole = $arrUser["user_role"];
                        $objUser = $this->projectService->userRepo->find($arrUser["user_uuid"]);
                        // $arrUserPermissionsInTeam = $this->getUserPermissionsInProject($objUser, $objProject);
                        if ($strRole === "Owner") {
                            foreach ($objSoundBlockDefaultPermissions as $objDefaultPermission) {
                                $strPermissionType = explode('.', $objDefaultPermission->permission_name)[2];
                                $strGroupName = $strPermissionType == "Project"
                                    ? "App.Soundblock.$strPermissionType.$objProject->project_uuid"
                                    : "App.Soundblock.$strPermissionType.{$objProject->account->account_uuid}";
                                $objCoreAuthGroup = DB::table("core_auth_groups")->where([
                                    "group_name" => $strGroupName
                                ])->first();
                                $userBasePermission = $this->getUserBasePermission($objUser, $objCoreAuthGroup, $objDefaultPermission->permission_uuid);
                                if ($userBasePermission->exists()) {
                                    $userBasePermission->update([
                                        "permission_value" => 1
                                    ]);
                                } else {
                                    DB::table('core_auth_permissions_groups_users')->insert([
                                        "row_uuid" => Util::uuid(),
                                        "group_id" => $objCoreAuthGroup->group_id,
                                        "group_uuid" => $objCoreAuthGroup->group_uuid,
                                        "user_id" => $objUser->user_id,
                                        "user_uuid" => $objUser->user_uuid,
                                        "permission_id"   => $objDefaultPermission->permission_id,
                                        "permission_uuid" => $objDefaultPermission->permission_uuid,
                                        "permission_value" => 1
                                    ]);
                                    $intCount++;
                                }
                            }
                            continue;
                        }
                    }
                }
            }
        });

        return "project Users permissions fix Operation Successful $intCount new records added";
    }

    public function setDefaultAccountUsersPermissions()
    {
        set_time_limit(0);
        $objAllAccount = accountModel::all();
        $intCount = 0;
        $objSoundBlockDefaultPermissions = $this->getDefaultSoundBlockAccountPermissions();
        $this->withProgressBar($objAllAccount, function ($objAccount) use (&$intCount, $objSoundBlockDefaultPermissions) {
            $objUser = $objAccount->user;
            foreach ($objSoundBlockDefaultPermissions as $objDefaultPermission) {
                $strGroupName = "App.Soundblock.Account.{$objAccount->account_uuid}";
                $objCoreAuthGroup = DB::table("core_auth_groups")->where([
                    "group_name" => $strGroupName
                ])->first();
                $userBasePermission = $this->getUserBasePermission($objUser, $objCoreAuthGroup, $objDefaultPermission->permission_uuid);
                if ($userBasePermission->exists()) {
                    $userBasePermission->update([
                        "permission_value" => 1
                    ]);
                } else {
                    DB::table('core_auth_permissions_groups_users')->insert([
                        "row_uuid" => Util::uuid(),
                        "group_id" => $objCoreAuthGroup->group_id,
                        "group_uuid" => $objCoreAuthGroup->group_uuid,
                        "user_id" => $objUser->user_id,
                        "user_uuid" => $objUser->user_uuid,
                        "permission_id"   => $objDefaultPermission->permission_id,
                        "permission_uuid" => $objDefaultPermission->permission_uuid,
                        "permission_value" => 1
                    ]);
                    $intCount++;
                }
            }
        });

        return "Account Users permissions fix Operation Successful $intCount new records added";
    }

    public function getUserBasePermission($objUser, $objCoreAuthGroup, $strPermissionUuid)
    {
        return DB::table("core_auth_permissions_groups_users")
            ->where([
                "permission_uuid" => $strPermissionUuid,
                "user_uuid"         => $objUser->user_uuid,
                "group_uuid" => $objCoreAuthGroup->group_uuid
            ]);
    }

    public function getUserPermissionsInProject($objUser, $objProject)
    {
        $authGroupService = resolve(AuthGroupService::class);
        $authPermissionService = resolve(AuthPermissionService::class);
        $projectGroup = $authGroupService->findByProject($objProject);
        $accountGroup = $authGroupService->findByAccount($objProject->account);
        $arrProjectPermission = $authPermissionService->findAllUserPermissionsByGroup($projectGroup, $objUser)->toArray();
        $arrAccountPermission = $authPermissionService->findAllUserPermissionsByGroup($accountGroup, $objUser)->toArray();
        $arrPermission = array_merge($arrAccountPermission, $arrProjectPermission);
        $arrPermission = array_values(array_map("unserialize", array_unique(array_map("serialize", $arrPermission))));
        return $arrPermission;
    }

    public function fixSoundblockPermissions()
    {
        $assignMissingAccountsPermissionsInGroup = $this->assignMissingAccountsPermissionsInGroup();
        $assignMissingProjectsPermissionsInGroup = $this->assignMissingProjectsPermissionsInGroup();
        $setPermissionGroupsToFalse = $this->setAllSoundblockPermissionGroupsToFalse();
        $setProjectUserPermissions = $this->setProjectAndAccountUsersPermissions();
        $setAccountUsersPermissions = $this->setDefaultAccountUsersPermissions();
        return [
            "project_group" => $assignMissingProjectsPermissionsInGroup,
            "account_group" => $assignMissingAccountsPermissionsInGroup,
            "group_permissions" => $setPermissionGroupsToFalse,
            "project_user" => $setProjectUserPermissions,
            "account_user" => $setAccountUsersPermissions
        ];
    }

    public function testingForProjectsWithoutATeam()
    {
        set_time_limit(0);
        $objAllProject = ProjectModel::all();
        $arrNoTeam = [];
        $this->withProgressBar($objAllProject, function ($objProject) use (&$arrNoTeam) {
            if (!$objProject->team) $arrNoTeam[$objProject->project_id] = $objProject->project_uuid;

        });
         dd($arrNoTeam);
    }


    public function getDefaultSoundBlockAccountPermissions()
    {
        return collect(DB::table("core_auth_permissions")->where("permission_name", "like", "%Soundblock.Account%")->get());
    }

    public function getDefaultSoundBlockPermissions(){
        return collect(DB::table("core_auth_permissions")->where("permission_name", "like", "%App.Soundblock%")->get());
     }




}
