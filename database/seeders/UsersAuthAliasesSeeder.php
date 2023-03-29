<?php

namespace Database\Seeders;

use App\Helpers\Util;
use Illuminate\Database\Eloquent\Model;
use App\Models\Users\User;
use App\Models\Users\Auth\UserAuthAlias;
use App\Models\BaseModel;
use Illuminate\Database\Seeder;

class UsersAuthAliasesSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        //
        Model::unguard();

        $aliases = [
            [//1
                "alias_uuid"   => Util::uuid(),
                "user_id"      => User::find(1)->user_id,
                "user_uuid"    => User::find(1)->user_uuid,
                "user_alias"   => "arescode",
                "flag_primary" => false,
            ],
            [//1
                "alias_uuid"   => Util::uuid(),
                "user_id"      => User::find(1)->user_id,
                "user_uuid"    => User::find(1)->user_uuid,
                "user_alias"   => "enetwizard",
                "flag_primary" => false,
            ],
            [//1
                "alias_uuid"   => Util::uuid(),
                "user_id"      => User::find(1)->user_id,
                "user_uuid"    => User::find(1)->user_uuid,
                "user_alias"   => "rswfire",
                "flag_primary" => false,
            ],
            [//1
                "alias_uuid"   => Util::uuid(),
                "user_id"      => User::find(1)->user_id,
                "user_uuid"    => User::find(1)->user_uuid,
                "user_alias"   => "samuel",
                "flag_primary" => false,
            ],
            [//1
                "alias_uuid"   => Util::uuid(),
                "user_id"      => User::find(1)->user_id,
                "user_uuid"    => User::find(1)->user_uuid,
                "user_alias"   => "swhite",
                "flag_primary" => true,
            ]
        ];

        foreach ($aliases as $alias) {
            UserAuthAlias::create($alias);
        }

        Model::reguard();
    }
}
