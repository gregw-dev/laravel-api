<?php

namespace App\Http\Requests\Office\Soundblock\Report;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\Office\Reports\UploadReportFile;

class UploadReports extends FormRequest
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
            "files" => "required|array",
            "files.*" => ["required", "file", "mimes:txt,csv,tsv,gz,zip", new UploadReportFile()]
        ];
    }
}
