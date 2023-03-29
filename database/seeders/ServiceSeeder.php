<?php

namespace Database\Seeders;

use App\Helpers\Constant;
use App\Helpers\Util;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Soundblock\Accounts\Account;
use App\Models\{BaseModel, Users\User, Core\Auth\AuthModel, Core\Auth\AuthPermission, Core\Auth\AuthGroup, Core\App};

class ServiceSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        //
        Model::unguard();

        $account = [
            [
                "account_uuid" => Util::uuid(),
                "user_id"      => User::find(1)->user_id,
                "user_uuid"    => User::find(1)->user_uuid,
                "account_name" => "Swhite@arena",
            ]
        ];

        Account::create($account);

        $arrAccountGroupPerms = Constant::account_level_permissions();

        foreach (Account::all() as $objAccount) {
            $objSoundblockAuthGroup = AuthGroup::create([
                "group_uuid"                => Util::uuid(),
                "auth_id"                   => AuthModel::where("auth_name", "App.Soundblock")->firstOrFail()->auth_id,
                "auth_uuid"                 => AuthModel::where("auth_name", "App.Soundblock")
                                                        ->firstOrFail()->auth_uuid,
                "group_name"                => "App.Soundblock." . "Account." . $objAccount->account_uuid,
                "group_memo"                => "Soundblock:Account Plan:" . $objAccount->account_name,
                BaseModel::STAMP_CREATED    => time(),
                BaseModel::STAMP_CREATED_BY => 1,
                BaseModel::STAMP_UPDATED    => time(),
                BaseModel::STAMP_UPDATED_BY => 1,
            ]);

            //Account Holder
            $objHolder = $objAccount->user;

            foreach ($arrAccountGroupPerms as $objAuthPerm) {
                $objSoundblockAuthGroup->permissions()->attach($objAuthPerm->permission_id, [
                    "row_uuid"                  => Util::uuid(),
                    "group_uuid"                => $objSoundblockAuthGroup->group_uuid,
                    "permission_uuid"           => $objAuthPerm->permission_uuid,
                    "permission_value"          => 1,
                    BaseModel::STAMP_CREATED    => time(),
                    BaseModel::STAMP_CREATED_BY => $objHolder->user_id,
                    BaseModel::STAMP_UPDATED    => time(),
                    BaseModel::STAMP_UPDATED_BY => $objHolder->user_id,
                ]);
            }
        }

        Model::reguard();
    }
}
