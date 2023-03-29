<?php

namespace App\Http\Requests\Soundblock\Project;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\Soundblock\Project\GetProjectsCopyrightYear;

class GetProjects extends FormRequest
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
            "per_page" => "sometimes|integer|between:10,100",
            "sort_by" => "sometimes|string|in:created,last_update,title,release",
            "sort_order" => "sometimes|string|in:asc,desc",
            "search" => "sometimes|string|max:255",

            "accounts" => "sometimes|array",
            "accounts.*" => "required_with:accounts|uuid|exists:soundblock_projects,account_uuid",
            "deployments" => "sometimes|array",
            "deployments.*" => "required_with:deployments|uuid|exists:soundblock_projects_deployments,platform_uuid",
            "genres" => "sometimes|array",
            "genres.*" => "required_with:genres|string|exists:soundblock_data_genres,data_genre",
            "artists" => "sometimes|array",
            "artists.*" => "required_with:artists|uuid|exists:soundblock_artists,artist_uuid",
            "contributors" => "sometimes|array",
            "contributors.*" => "required_with:contributors|uuid|exists:soundblock_contributors,contributor_uuid",
            "formats" => "sometimes|array",
            "formats.*" => "required_with:formats|uuid|exists:soundblock_data_projects_formats,data_uuid",

            "record_label" => "sometimes|string|max:255",

            "release_date_starts" => "required_with:release_date_ends|date",
            "release_date_ends" => "required_with:release_date_starts|date|after_or_equal:release_date_starts",
            "copyright_year_starts" => "required_with:copyright_date_ends|date_format:Y",
            "copyright_year_ends" => ["required_with:copyright_year_starts", "date_format:Y", new GetProjectsCopyrightYear($this->all())],

            "explicit" => "sometimes|boolean"
        ];
    }
}
