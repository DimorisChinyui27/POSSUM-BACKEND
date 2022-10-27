<?php

namespace App\Http\Resources;

use App\Models\Media;
use Illuminate\Contracts\Support\Arrayable;
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
                'username' => $this->user->name,
                'img' => null,
            ],
            'answers' => [],
            'media' => $this->media->transform(function (Media $media) {
                return [
                    'type' => array_search(strtolower($media->file_type), videoFormat()) ? 'video' : 'image',
                    'path' => asset('storage/files/questions/' . $media->file_name)
                ];
            }),
        ];
    }
}
