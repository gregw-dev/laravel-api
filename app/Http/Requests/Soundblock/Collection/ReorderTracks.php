<?php

namespace App\Http\Requests\Soundblock\Collection;

use Illuminate\Foundation\Http\FormRequest;

class ReorderTracks extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return (true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return ([
            "collection" => "required|uuid|exists:soundblock_collections,collection_uuid",
            "track" => "required|uuid|exists:soundblock_tracks,track_uuid",
            "position" => "required|numeric",
        ]);
    }
}
