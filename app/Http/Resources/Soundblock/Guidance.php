<?php

namespace App\Http\Resources\Soundblock;

use Illuminate\Http\Resources\Json\JsonResource;

class Guidance extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // $arrData = parent::toArray($request);
        return [
        "guide_ref" => $this->guide_ref,
        "guide_title"  => $this->guide_title,
        "guide_html" => $this->guide_html
        ];
    }
}
