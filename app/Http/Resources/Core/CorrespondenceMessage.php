<?php

namespace App\Http\Resources\Core;

use Illuminate\Http\Resources\Json\JsonResource;

class CorrespondenceMessage extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($objRequest)
    {
        $objData =  parent::toArray($objRequest);

        return [
            "message_uuid" => $objData['message_uuid'],
            "user_email" => is_null($this->user_id) ? $this->user_email : null,
            $this->merge($objData)
        ];
    }
}
