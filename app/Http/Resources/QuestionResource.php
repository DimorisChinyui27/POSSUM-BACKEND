<?php

namespace App\Http\Resources;

use App\Models\Media;
use App\Models\User;
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
            'is_anonymous' => (boolean)$this->is_anonymous,
            'target_type' => $this->target,
            'has_correct_answer' => $this->has_correct_answer?:false,
            'user' => [
                'id' => $this->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
                'headline' => $this->headline,
                'img' => asset('images/avatar.jpeg'),
            ],
            'has_contribute' => $request->user() ? $this->transactions()->where('user_id', $request->user()->id)
                ->where('type', 'GIFT_QUESTION')
                ->where('status', 'pending')->exists() : false,
            'gift' => $this->gift?:0.0,
            'has_voted' => $request->user() ? $this->isVotedBy($request->user()) : false,
            'total_voters' => $this->votersCount()?:0,
            'comments_count' => $this->comments()->count()?: 0,
            'answers_count' => $this->answers_count?:0,
            'answers' => AnswerResource::collection($this->answers()->top()->limit(15)->get()),
            'topics'=> TopicResource::collection($this->topics),
            'target_users' => $this->target == 'user' ? $this->targets->transform(function (User $user) {
               return [
                   'id' => $user->id,
                   'username' => $user->username,
                   'name' => $user->name,
                   'headline' => $this->headline,
                   'img' => asset('images/avatar.jpeg'),
               ] ;
            }) : null,
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
