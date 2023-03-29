<?php

namespace App\Http\Requests\Music\Artist;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Create extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            "artist_name"            => "required|string",
            "active_date"            => "sometimes|string",
            "born"                   => "sometimes|string",
            "allmusic_url"           => "sometimes|url",
            "amazon_url"             => "sometimes|url",
            "itunes_url"             => "sometimes|url",
            "lastfm_url"             => "sometimes|url",
            "spotify_url"            => "sometimes|url",
            "wikipedia_url"          => "sometimes|url",
            "genres"                 => "sometimes|array",
            "genres.*"               => "sometimes|string",
            "styles"                 => "sometimes|array",
            "styles.*"               => "sometimes|string",
            "themes"                 => "sometimes|array",
            "themes.*"               => "sometimes|string",
            "moods"                  => "sometimes|array",
            "moods.*"                => "sometimes|string",
            "members"                => "sometimes|array",
            "members.*.member"       => "sometimes|array",
            "members.*.url_allmusic" => "sometimes|string",
        ];
    }
}
