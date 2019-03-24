<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ErrorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $request = parent::toArray($request);
        return [
            "status" => "error",
            "message" => $request["message"],
            "data" => isset($request['data']) ? $request['data'] : null
        ];
    }
}
