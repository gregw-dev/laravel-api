<?php

namespace Database\Seeders;

use App\Models\Soundblock\Platform;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class SoundblockPlatformSeeder extends Seeder {
    const CATEGORIES = ["music", "video", "merchandising"];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        Model::unguard();

        $platforms = [
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "7 Digital (UK)",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Akazoo (UK)",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Anghami (United Arab Emirates)",
                "flag_music"         => true,
                "flag_video"         => true,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Amazon",
                "flag_music"         => false,
                "flag_video"         => false,
                "flag_merchandising" => true,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Amazon Music",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Apple Music",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Arena",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => true,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Audible Magic",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Bandcamp",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Boomplay (Africa)",
                "flag_music"         => true,
                "flag_video"         => true,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Deezer (France)",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Dubset",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Facebook/Instagram",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "iHeartRadio",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Jaxsta Music (Australia)",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Joox (China)",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Juno Download (UK)",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Napster",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "MixCloud (UK)",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "VEVO",
                "flag_music"         => false,
                "flag_video"         => true,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Pandora",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Peloton",
                "flag_music"         => false,
                "flag_video"         => false,
                "flag_merchandising" => true,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Resso (China)",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Sber Zvuk (Russia)",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Shazam",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Shopify",
                "flag_music"         => false,
                "flag_video"         => false,
                "flag_merchandising" => true,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Soundcloud",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Slacker Radio",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Snapchat",
                "flag_music"         => true,
                "flag_video"         => true,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Spotify",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Tencent Music Entertainment (China)",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "TikTok (China)",
                "flag_music"         => false,
                "flag_video"         => true,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Traxsource",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Triller",
                "flag_music"         => false,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Uma Music (Australia)",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Yandex (Russia)",
                "flag_music"         => true,
                "flag_video"         => false,
                "flag_merchandising" => false,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "Your Band Website",
                "flag_music"         => false,
                "flag_video"         => false,
                "flag_merchandising" => true,
            ],
            [
                "platform_uuid"      => strtoupper((string)\Str::uuid()),
                "name"               => "YouTube",
                "flag_music"         => false,
                "flag_video"         => true,
                "flag_merchandising" => false,
            ]
        ];

        foreach ($platforms as $platform) {
            $objPlatform = new Platform();
            $objPlatform->create($platform);
        }

        Model::reguard();
    }
}
