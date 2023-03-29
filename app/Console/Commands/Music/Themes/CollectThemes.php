<?php

namespace App\Console\Commands\Music\Themes;

use App\Helpers\Util;
use App\Models\Music\Artist\ArtistTheme as Theme;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Music\Project\Project;

class CollectThemes extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'music:themes';

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
        $themes = DB::table("projects_themes")->select(["project_theme", "project_id"]);

        foreach ($themes as $theme) {
            $objProject = Project::find($theme->project_id);
            if (Theme::where(["artist_id", $objProject->artist_id, "artist_theme", $theme->project_theme])->doesntExist()) {
                Theme::create([
                    "row_uuid" => Util::uuid(),
                    "artist_theme" => $theme->project_theme,
                    "artist_id" => $objProject->artist_id,
                    "artist_uuid" => $objProject->artist_uuid
                ]);
            }
        }

        return 0;
    }
}
