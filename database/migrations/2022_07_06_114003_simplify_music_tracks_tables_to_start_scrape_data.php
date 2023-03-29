<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SimplifyMusicTracksTablesToStartScrapeData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("music_projects_tracks_genres", function (Blueprint $objTable) {
            $objTable->dropForeign("music_projects_tracks_genres_track_id_foreign");
            $objTable->dropForeign("music_projects_tracks_genres_track_uuid_foreign");

            $objTable->dropColumn("genre_id");
            $objTable->dropColumn("genre_uuid");
            $objTable->dropIndex("idx_track-id_genre_id");
            $objTable->dropIndex("idx_genre-id_track-id");
            $objTable->dropIndex("idx_track-uuid_genre_uuid");
            $objTable->dropIndex("idx_genre-uuid_track-uuid");

            $objTable->string("track_genre")->after("track_uuid")->index("idx_track-genre");
        });

        Schema::table("music_projects_tracks_styles", function (Blueprint $objTable) {
            $objTable->dropForeign("music_projects_tracks_styles_track_id_foreign");
            $objTable->dropForeign("music_projects_tracks_styles_track_uuid_foreign");

            $objTable->dropColumn("style_id");
            $objTable->dropColumn("style_uuid");
            $objTable->dropIndex("idx_track-id_style_id");
            $objTable->dropIndex("idx_style-id_track-id");
            $objTable->dropIndex("idx_track-uuid_style_uuid");
            $objTable->dropIndex("idx_style-uuid_track-uuid");

            $objTable->string("track_style")->after("track_uuid")->index("idx_track-style");
        });

        Schema::table("music_projects_tracks_themes", function (Blueprint $objTable) {
            $objTable->dropForeign("music_projects_tracks_themes_track_id_foreign");
            $objTable->dropForeign("music_projects_tracks_themes_track_uuid_foreign");

            $objTable->dropColumn("theme_id");
            $objTable->dropColumn("theme_uuid");
            $objTable->dropIndex("idx_track-id_theme_id");
            $objTable->dropIndex("idx_theme-id_track-id");
            $objTable->dropIndex("idx_track-uuid_theme_uuid");
            $objTable->dropIndex("idx_theme-uuid_track-uuid");

            $objTable->string("track_theme")->after("track_uuid")->index("idx_track-theme");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("music_projects_tracks_genres", function (Blueprint $objTable) {
            $objTable->dropColumn("track_genre");
            $objTable->dropIndex("idx_track-genre");

            $objTable->unsignedBigInteger("genre_id")->index("idx_genre-id");
            $objTable->uuid("genre_uuid")->index("idx_genre-uuid");

            $objTable->index(["track_id", "genre_id"], "idx_track-id_genre_id");
            $objTable->index(["genre_id", "track_id"], "idx_genre-id_track-id");
            $objTable->index(["track_uuid", "genre_uuid"], "idx_track-uuid_genre_uuid");
            $objTable->index(["genre_uuid", "track_uuid"], "idx_genre-uuid_track-uuid");
        });

        Schema::table("music_projects_tracks_styles", function (Blueprint $objTable) {
            $objTable->dropColumn("track_style");
            $objTable->dropIndex("idx_track-style");

            $objTable->unsignedBigInteger("style_id")->index("idx_style-id");
            $objTable->uuid("style_uuid")->index("idx_style-uuid");

            $objTable->index(["track_id", "style_id"], "idx_track-id_style_id");
            $objTable->index(["style_id", "track_id"], "idx_style-id_track-id");
            $objTable->index(["track_uuid", "style_uuid"], "idx_track-uuid_style_uuid");
            $objTable->index(["style_uuid", "track_uuid"], "idx_style-uuid_track-uuid");
        });

        Schema::table("music_projects_tracks_themes", function (Blueprint $objTable) {
            $objTable->dropColumn("track_theme");
            $objTable->dropIndex("idx_track-theme");

            $objTable->unsignedBigInteger("theme_id")->index("idx_theme-id");
            $objTable->uuid("theme_uuid")->index("idx_theme-uuid");

            $objTable->index(["track_id", "theme_id"], "idx_track-id_theme_id");
            $objTable->index(["theme_id", "track_id"], "idx_theme-id_track-id");
            $objTable->index(["track_uuid", "theme_uuid"], "idx_track-uuid_theme_uuid");
            $objTable->index(["theme_uuid", "track_uuid"], "idx_theme-uuid_track-uuid");
        });
    }
}
