<?php

namespace App\Models\Music\Project;

use App\Helpers\Filesystem\Music;
use App\Models\BaseModel;
use App\Models\Music\Artist\Artist;
use App\Models\Music\Artist\ArtistGenre;
use App\Models\Music\Artist\ArtistMood;
use App\Models\Music\Artist\ArtistStyle;
use App\Models\Music\Artist\ArtistTheme;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectTrack extends BaseModel {
    use HasFactory;

    protected $table = "music_projects_tracks";
    protected $hidden = [
        "row_id", "project_id", BaseModel::CREATED_AT, BaseModel::UPDATED_AT,
        BaseModel::DELETED_AT, BaseModel::STAMP_DELETED, BaseModel::STAMP_DELETED_BY,
    ];
    protected $primaryKey = "track_id";
    protected string $uuid = "track_uuid";
    protected $guarded = [];

    protected bool $ignoreBootEvents = true;

    public function project() {
        return ($this->belongsTo(Project::class, "project_id", "project_id"));
    }

    public function composers() {
        return ($this->belongsToMany(Artist::class, "music_projects_tracks_composers", "track_id", "artist_id", "track_id", "artist_id"));
    }

    public function features() {
        return ($this->belongsToMany(Artist::class, "music_projects_tracks_features", "track_id", "artist_id", "track_id", "artist_id"));
    }

    public function performers() {
        return ($this->belongsToMany(Artist::class, "music_projects_tracks_performers", "track_id", "artist_id", "track_id", "artist_id"));
    }

   public function genres() {
       return $this->belongsToMany(ArtistGenre::class, "music_projects_tracks_genres",  "track_id", "genre_id", "track_id", "row_id");
   }

   public function moods() {
    return $this->belongsToMany(ArtistMood::class, "music_projects_tracks_moods", "track_id", "mood_id", "track_id", "row_id");
   }

   public function styles() {
       return $this->belongsToMany(ArtistStyle::class,"music_projects_tracks_styles", "track_id", "style_id", "track_id", "row_id");
   }

   public function themes() {
       return $this->belongsToMany(ArtistTheme::class,"music_projects_tracks_themes", "track_id", "theme_id", "track_id", "row_id");
   }

   public function getUploadedAttribute(){
       if (bucket_storage("music")->exists(Music::project_track_path($this->project, $this->track_uuid))) {
           return (true);
       }

       return (false);
   }
}
