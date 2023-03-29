<?php

namespace App\Models\Music\Artist;

use App\Models\BaseModel;
use App\Models\Music\Project\Project;
use App\Traits\BaseScalable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Artist extends BaseModel
{
    use HasFactory;
    use BaseScalable;
    protected $table = "music_artists";

    protected $hidden = [
        "artist_id", "arena_id", "pivot", BaseModel::CREATED_AT, BaseModel::UPDATED_AT,
        BaseModel::DELETED_AT, BaseModel::STAMP_DELETED, BaseModel::STAMP_DELETED_BY,"stamp_epoch","stamp_date","stamp_time"
    ];

    protected $primaryKey = "artist_id";

    protected string $uuid = "artist_uuid";

    protected $guarded = [];

    public bool $ignoreBootEvents = true;

    public $metaData = [
        "filters" => [
            "flag_allmusic" => [
                "column" => "flag_allmusic"
            ],
            "flag_amazon" => [
                "column" => "flag_amazon"
            ],
            "flag_itunes" => [
                "column" => "flag_itunes"
            ],
            "flag_lastfm" => [
                "column" => "flag_lastfm"
            ],
            "flag_spotify" => [
                "column" => "flag_spotify"
            ],
            "flag_wikipedia" => [
                "column" => "flag_wikipedia"
            ]
        ],
        "search" => [
            "artist_name" => [
                "column" => "artist_name"
            ],
            "artist_active" => [
                "column" => "artist_active"
            ],
            "artist_born" => [
                "column" => "artist_born"
            ],
        ],
        "sort" => [
            "flag_allmusic" => [
                "column" => "flag_allmusic"
            ],
            "flag_amazon" => [
                "column" => "flag_amazon"
            ],
            "flag_itunes" => [
                "column" => "flag_itunes"
            ],
            "flag_lastfm" => [
                "column" => "flag_lastfm"
            ],
            "flag_spotify" => [
                "column" => "flag_spotify"
            ],
            "flag_wikipedia" => [
                "column" => "flag_wikipedia"
            ],
            "artist_name" => [
                "column" => "artist_name"
            ],
            "artist_active" => [
                "column" => "artist_active"
            ],
            "artist_born" => [
                "column" => "artist_born"
            ],
        ]
    ];

    public function projects() {
        return $this->hasMany(Project::class, "artist_id", "artist_id");
    }

    public function alias() {
        return $this->hasMany(ArtistAlias::class, "artist_id", "artist_id");
    }

//    public function genres() {
//        return ($this->belongsToMany(Genre::class, "music_artists_genres", "artist_id", "genre_id", "artist_id", "genre_id"));
//    }
//
    public function genres() {
        return $this->hasMany(ArtistGenre::class, "artist_id", "artist_id");
    }

    public function members() {
        return $this->belongsToMany(Artist::class, "music_artists_members", "artist_id", "artist_id", "artist_id", "artist_id");
    }
//
//    public function styles() {
//        return ($this->belongsToMany(Style::class, "music_artists_styles", "artist_id", "style_id", "artist_id", "style_id"));
//    }
//
    public function styles() {
        return ($this->hasMany(ArtistStyle::class, "artist_id", "artist_id"));
    }

//    public function themes() {
//        return ($this->belongsToMany(Theme::class, "music_artists_themes", "artist_id", "theme_id", "artist_id", "theme_id"));
//    }

    public function themes() {
        return ($this->hasMany(ArtistTheme::class, "artist_id", "artist_id"));
    }

    public function influenced() {
        return $this->hasMany(ArtistInfluenced::class, "artist_id", "artist_id");
    }

//    public function moods() {
//        return ($this->belongsToMany(Mood::class, "artists_moods", "artist_id", "mood_id", "artist_id", "mood_id"));
//    }

    public function moods() {
        return $this->hasMany(ArtistMood::class, "artist_id", "artist_id");
    }

    public function related()
    {
        return $this->hasMany(ArtistRelated::class,"artist_id","artist_id");
    }

    public function similar()
    {
        return $this->hasMany(ArtistSimilar::class,"artist_id","artist_id");
    }

}
