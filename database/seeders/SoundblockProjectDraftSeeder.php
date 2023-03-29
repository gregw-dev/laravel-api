<?php

namespace Database\Seeders;

use App\Helpers\Util;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Soundblock\Accounts\Account;
use App\Models\Soundblock\Projects\ProjectDraft;

class SoundblockProjectDraftSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        //
        Model::unguard();

        $drafts = [
            [
                "draft_uuid"   => Util::uuid(),
                "account_id"   => Account::find(1)->account_id,
                "account_uuid" => Account::find(1)->account_uuid,
                "draft_json"   => [
                    "project" => [
                        "project_title"  => "The project for a draft",
                        "project_type"   => "Album",
                        "project_date"   => Util::today(),
                        "project_file"   => true,
                        "project_avatar" => "",
                    ],
                    "payment" => [
                        "members"  => [
                            [
                                "name"   => "Vladyslav Karpenko",
                                "email"  => "vkarpenko@arena.com",
                                "role"   => "Band Member",
                                "Payout" => 80,
                            ],
                            [
                                "name"   => "Scam",
                                "email"  => "scam@arena.com",
                                "role"   => "Lawyer",
                                "Payout" => 80,
                            ],
                        ],
                        "contract" => [
                            "payment_message" => "Custom Payment Message",
                            "name"            => "Rocky",
                            "email"           => "rocky@gmail.com",
                            "phone"           => "13833798",
                        ],
                    ],
                ],
            ]
        ];

        foreach ($drafts as $draft) {
            ProjectDraft::create($draft);
        }

        Model::reguard();
    }
}
