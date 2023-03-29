<?php

namespace App\Http\Requests\Music\Artist;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Update extends FormRequest
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
            "artist_name"            => "sometimes|string",
            "artist_active"          => "sometimes||string",
            "artist_born"            => "sometimes|string",
            "url_allmusic"           => "sometimes|url",
            "url_amazon"             => "sometimes|url",
            "url_itunes"             => "sometimes|url",
            "url_lastfm"             => "sometimes|url",
            "url_spotify"            => "sometimes|url",
            "url_wikipedia"          => "sometimes|url",
            "genres"                 => "sometimes|array",
            "genres.*"               =>  "required_with:genres|string",
            "styles"                 => "sometimes|array",
            "styles.*"               => "required_with:styles|string",
            "themes"                 => "sometimes|array",
            "themes.*"               => "required_with:themes|string",
            "moods"                  => "sometimes|required|array",
            "moods.*"                => "required_with:moods|string",
            "members"                => "sometimes|array",
            "members.*"              => "sometimes|required_with:members|array"
        ];
    }
}
