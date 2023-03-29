<?php

namespace App\Console\Commands\Music\Genre;

use App\Helpers\Util;
use App\Models\Music\Artist\ArtistGenre as GenreModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Music\Project\Project;

class Genre extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'music:genres';

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

        $genres = DB::table("music_projects_genres")->select(["project_genre", "project_id"]);

        foreach ($genres as $genre) {
            $objProject = Project::find($genre->project_id);
            if (GenreModel::where(["artist_genre" => $genre->project_genre, "project_id" => $objProject->project_id])->doesntExist()) {
                GenreModel::create([
                    "row_uuid" => Util::uuid(),
                    "artist_genre"      => $genre->project_genre,
                    "artist_id" => $objProject->artist_id,
                    "artist_uuid" => $objProject->artist_uuid
                ]);
            }
        }

        return 0;
    }
}
