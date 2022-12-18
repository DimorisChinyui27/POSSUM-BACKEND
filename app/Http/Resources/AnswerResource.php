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
            'id' => $this->id,
            'body' => $this->body,
            'has_voted' => $request->user() ? $this->isVotedBy($request->user()) : false,
            'total_voters' => $this->votersCount()?:0,
            'user' => [
                'username' => $this->user->username,
                'name' => $this->user->name,
                'img' => $this->user->img
            ],
            'question' => [
                'id' => $this->question->id,
                'title' => $this->question->title,
                'body' => $this->question->body
            ],
            'satisfy' =>(boolean)$this->satisfy
        ];
    }
}
