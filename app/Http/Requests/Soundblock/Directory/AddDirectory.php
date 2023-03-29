<?php

namespace App\Http\Requests\Soundblock\Directory;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\Soundblock\Collection\AddDirectory as AddDirectoryRule;

class AddDirectory extends FormRequest {

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return (true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */

    public function rules() {
        return ([
            "project"            => "required|uuid|exists:soundblock_projects,project_uuid",
            "collection_comment" => "required|string|max:255",
            "directory_path"     => "required|string|max:255",
            "directory_name"     => ["required", "string", "max:255", new AddDirectoryRule($this->all())],
            "directory_category" => "required|string|in:Merch,Files",
        ]);
    }
}
