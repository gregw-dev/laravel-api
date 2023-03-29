<?php

namespace App\Http\Requests\Music\Project;

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
            "project_name" => "required|string",
            "project_type" => "required|string",
            "project_date" => "required|date",
            "project_label" => "required|string",
            "project_year" => "required|string",
            "project_duration" => "required|string",
            "url_allmusic"   => "sometimes | url",
            "url_spotify"   => "sometimes | url",
            "url_amazon"   => "sometimes | url",
            "artist" => "required|uuid",
            "tracks"                 => "required_with:file|array",
            "tracks.*.name"          => "required_with:tracks|string",
            "tracks.*.original_name" => "required_with:tracks|string",
            "tracks.*.disc_number"   => "required_with:tracks|integer",
            "tracks.*.track_number"  => "required_with:tracks|integer",
            "tracks.*.url_amazon"    => "sometimes|required_with:tracks|url",
            "tracks.*.url_spotify"   => "sometimes|required_with:tracks|url",
            "tracks.*.url_allmusic"  => "sometimes|required_with:tracks|url",
            "tracks.*.composers"     => "required_with:tracks|array",
            "tracks.*.composers.*"   => "required_with:tracks|string|uuid|exists:music_artists,artist_uuid",
            "tracks.*.performers"    => "required_with:tracks|array",
            "tracks.*.performers.*"  => "required_with:tracks|string|uuid|exists:music_artists,artist_uuid",
            "tracks.*.features"      => "sometimes|required_with:tracks|array",
            "tracks.*.features.*"    => "sometimes|required_with:tracks|string|uuid|exists:music_artists,artist_uuid",
            "file"                   => "required_with:tracks|file|mimes:zip",
        ];
    }
}
