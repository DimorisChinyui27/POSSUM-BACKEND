<?php

namespace App\Http\Resources;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
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
            'title' => $this->title,
            'body' => $this->body,
            'slug' => $this->slug,
            'user' => [
                'name' => $this->user->name,
                'username' => $this->user->username,
                'img' => asset('images/avatar.jpeg'),
            ],
            'gift' => $this->gift?:0.0,
            'has_voted' => $request->user() ? $this->isVotedBy($request->user()) : false,
            'total_voters' => $this->votersCount()?:0,
            'comments_count' => $this->comments()->count()?: 0,
            'answers_count' => $this->answers_count?:0,
            'answers' => [],
            'topics'=> TopicResource::collection($this->topics),
            'media' => $this->media->transform(function (Media $media) {
                return [
                    'id' => $media->id,
                    'type' => array_search(strtolower($media->file_type), videoFormat()) ? 'video' : 'image',
                    'url' => asset('storage/files/questions/' . $media->file_name)
                ];
            }),
        ];
    }
}
