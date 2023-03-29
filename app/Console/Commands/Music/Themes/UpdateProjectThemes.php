<?php

namespace App\Console\Commands\Music\Themes;

use App\Models\Music\Project\ProjectTheme;
use Illuminate\Console\Command;

class UpdateProjectThemes extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:themes:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $arrThemes = \App\Models\Music\Theme::all();

        // foreach ($arrThemes as $theme) {
        //     ProjectTheme::where("project_theme", $theme->theme_name)->update([
        //         "theme_id"   => $theme->theme_id,
        //         "theme_uuid" => $theme->theme_uuid,
        //     ]);
        // }

        return 0;
    }
}
