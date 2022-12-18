<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    private int $level;

    public function __construct($resource, $level = 1)
    {
        $this->level = $level;
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $array = [
            'id' => $this->id,
            'name' => $this->name,
            'language' => $this->language,
            'email' => $this->email,
            'username' => $this->username,
            'headline' => $this->headline,
            'topics' => TopicResource::collection($this->topics),
            'img' => $this->img
        ];
        if ($this->level != 1) {
            $array = array_merge($array, [
                'about' => $this->about,
                'answers_count' => $this->answers_count,
                'questions_count' => $this->questions_count,
                'voter_votes_count' => $this->voterVotes()->count()
            ]);
            if ($this->level == 3) {
                $array = array_merge($array, [
                    'dob' => $this->dob,
                    'bio' => $this->bio,
                    'address' => $this->address,
                    'gender' => $this->gender,
                    'country' => new CountryResource($this->country),
                    'city' => $this->city
                ]);
            }
        }
        return $array;
    }
}
