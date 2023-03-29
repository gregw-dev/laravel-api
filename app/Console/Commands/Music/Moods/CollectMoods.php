<?php

namespace App\Console\Commands\Music\Moods;

use App\Models\Music\Project\Project;
use App\Helpers\Util;
use App\Models\Music\Artist\ArtistMood as Mood;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CollectMoods extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'music:mood';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Group Genres';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $objMoods = DB::table("music_projects_moods")->select(["project_mood", "project_id"]);
        foreach ($objMoods as $objMood) {
        $objProject = Project::find($objMood->project_id);

            if (Mood::where(["artist_mood" => $objMood->project->mood, "artist_id" => $objProject->artist_id])->doesntExist()) {
                Mood::create([
                    "row_uuid" => Util::uuid(),
                    "artist_mood"      => $objMood->project_mood,
                    "artist_id" => $objProject->artist_id,
                    "artist_uuid" => $objProject->artist_uuid
                ]);
            }
        }

        // $genres = DB::table("music_artists_moods")->groupBy("artist_mood")
        //             ->pluck("artist_mood");

        // foreach ($genres as $genre) {
        //     if (Mood::where("artist_mood", $genre)->doesntExist()) {
        //         Mood::create([
        //             "mood_uuid" => Util::uuid(),
        //             "artist_mood"      => $genre,
        //         ]);
        //     }
        // }

        return 0;
    }
}
