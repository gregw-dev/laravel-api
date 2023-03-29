<?php

namespace App\Http\Requests\Soundblock\Project\Deploy;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\Soundblock\Project\CreateDeployment as CreateDeploymentRule;

class CreateDeployment extends FormRequest {

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
        return [
            "youtube_agreement" => ["nullable", "boolean"],
//            "platforms"   => ["required", "array", new CreateDeploymentRule($this->all())],
            "platforms"   => ["required", "array"],
            "platforms.*" => "required|uuid",
        ];
    }
}
