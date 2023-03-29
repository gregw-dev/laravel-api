<?php

namespace App\Http\Requests\Music\Project\Draft;

use Illuminate\Foundation\Http\FormRequest;

class Create extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "artist"                 => "required|uuid|exists:music_artists,artist_uuid",
            "project_type"           => "required|string",
            "project_date"           => "required|date",
            "project_name"           => "required|string",
            "project_label"          => "required|string",
            "url_allmusic"           => "sometimes|string|url",
            "url_amazon"             => "sometimes|string|url",
            "url_itunes"             => "sometimes|url",
            "url_spotify"            => "sometimes|string|url",
            "tracks"                 => "sometimes|array",
            "tracks.*.name"          => "required_with:tracks|string",
            "tracks.*.original_name" => "required_with:tracks|string",
            "tracks.*.disc_number"   => "required_with:tracks|integer",
            "tracks.*.track_number"  => "required_with:tracks|integer",
            "tracks.*.url_amazon"    => "required_with:tracks|url",
            "tracks.*.url_spotify"   => "required_with:tracks|url",
            "tracks.*.url_allmusic"  => "required_with:tracks|url",
            "tracks.*.composers"     => "required_with:tracks|array",
            "tracks.*.composers.*"   => "required_with:tracks|string|uuid|exists:music_artists,artist_uuid",
            "tracks.*.performers"    => "required_with:tracks|array",
            "tracks.*.performers.*"  => "required_with:tracks|string|uuid|exists:music_artists,artist_uuid",
            "tracks.*.features"      => "required_with:tracks|array",
            "tracks.*.features.*"    => "required_with:tracks|string|uuid|exists:music_artists,artist_uuid",
            "file"                   => "required|file|mimes:zip",
            "genres"                 => "required|array",
            "genres.*"               => "required_with:genres|string|exists:music_artists_genres,artist_genre",
            "moods"                  => "sometimes|array",
            "moods.*"                => "required_with:moods|string|exists:music_artists_moods,mood_uuid",
            "styles"                 => "sometimes|array",
            "styles.*"               => "required_with:styles|string|exists:music_artists_genres,style_uuid",
            "themes"                 => "sometimes|array",
            "themes.*"               => "required_with:themes|string|exists:music_artists_themes,theme_uuid",
        ];
    }
}
