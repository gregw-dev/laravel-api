<?php

namespace Database\Seeders;

use App\Helpers\Util;
use App\Models\Users\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        //$arrUsers = factory(User::class)->times(50)->create();
        Model::unguard();

        $users = [
            [
                "user_uuid" => Util::uuid(),
                "user_password" => Hash::make("TurTl3s"),
                "name_first" => "Samuel",
                "name_last" => "White",
                "remember_token" => Str::random(10),
            ],
        ];

        foreach($users as $user)
        {
            User::create($user);

        }

        Model::reguard();
    }
}
