<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnswerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'body' => $this->body,
            'has_voted' => $request->user() ? $this->isVotedBy($request->user()) : false,
            'total_voters' => $this->votersCount()?:0,
            'comments_count' => $this->comments()->count()?: 0,
            'answers_count' => $this->answers_count?:0,
            'user' => [
                'username' => $this->user->username,
                'name' => $this->user->name,
                'img' => $this->user->img
            ],
        ];
    }
}
