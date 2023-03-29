<?php

namespace Database\Seeders;

use App\Helpers\Util;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use App\Models\Users\User;
use App\Models\Users\Contact\UserContactEmail;
use Illuminate\Support\Carbon;

class UsersEmailsSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        //
        Model::unguard();

        $userEmail = [
            "row_uuid"                       => Util::uuid(),
            "user_id"                        => User::find(1)->user_id,
            "user_uuid"                      => User::find(1)->user_uuid,
            "user_auth_email"                => "swhite@arena.com",
            "flag_primary"                   => true,
            "flag_verified"                  => true,
            UserContactEmail::EMAIL_AT       => Util::now(),
            UserContactEmail::STAMP_EMAIL    => time(),
            UserContactEmail::STAMP_EMAIL_BY => 1,
        ];

        UserContactEmail::create($userEmail);

        Model::reguard();
    }
}
