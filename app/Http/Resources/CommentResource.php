<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request): array|JsonSerializable|Arrayable
    {
        return [
            'id' => $this->id,
            'commentable_id' => $this->commentable_id,
            'replies' => $this->replies ? $this->replies->transform(function ($v) {
                return new CommentResource($v);
            }) : [],
            'user' => (object) [
                'id' => $this->commented->id,
                'username' => $this->commented->username,
                'picture' => $this->commented->picture,
                'img' => asset('images/avatar.jpeg'),
            ],
            'message' => $this->comment
        ];
    }
}
