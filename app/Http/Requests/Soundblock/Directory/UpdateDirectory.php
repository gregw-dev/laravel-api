<?php

namespace App\Http\Requests\Soundblock\Directory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDirectory extends FormRequest {

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
            "directory"          => "required|uuid|exists:soundblock_files_directories,directory_uuid",
            "directory_name"     => "required|string|max:255",
        ]);
    }
}
