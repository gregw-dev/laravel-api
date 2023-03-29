<?php

namespace App\Http\Requests\Soundblock\Artist;

use Illuminate\Foundation\Http\FormRequest;

class StoreArtist extends FormRequest
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
            "account"        => "required|uuid|exists:soundblock_accounts,account_uuid",
            "project_uuid"   => "sometimes|uuid|exists:soundblock_projects,project_uuid",
            "artist_name"    => "required|string|max:255",
            "url_apple"      => ["sometimes", "nullable", "url", "regex:(apple)"],
            "url_soundcloud" => ["sometimes", "nullable", "url", "regex:(soundcloud)"],
            "url_spotify"    => ["sometimes", "nullable", "url", "regex:(spotify)"],
            "avatar"         => "required|file|mimes:jpeg,jpg,png,bmp,tiff|dimensions:min_width=2400,min_height=2400|dimensions:ratio=1/1"
        ];
    }
}
