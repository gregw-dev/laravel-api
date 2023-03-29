<?php

namespace App\Console\Commands\Music\Styles;

use App\Helpers\Util;
use App\Models\Music\Artist\ArtistStyle as Style;
use App\Models\Music\Project\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CollectStyle extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'music:style';

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
        $styles = DB::table("Music_projects_styles")->select(["project_style", "project_id", "project_uuid"]);


        foreach ($styles as $style) {
            $objProject = Project::find($style->project_id);
            if (Style::where(["artist_style" => $style->project_style, "artist_uuid" => $objProject->artist_uuid])->doesntExist()) {
                Style::create([
                    "row_uuid" => Util::uuid(),
                    "artist_style" => $style->project_style,
                    "artist_id" => $objProject->artist_id,
                    "artist_uuid" => $objProject->artist_uuid
                ]);
            }
        }

        // $styles = DB::table("music_artists_styles")->groupBy("artist_style")
        //             ->pluck("artist_style");

        // foreach ($styles as $style) {
        //     if (Style::where("style_name", $style)->doesntExist()) {
        //         Style::create([
        //             "style_uuid" => Util::uuid(),
        //             "style_name" => $style,
        //         ]);
        //     }
        // }

        return 0;
    }
}
