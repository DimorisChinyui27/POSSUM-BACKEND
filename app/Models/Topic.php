<?php


namespace App\Models;


use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\Translatable\HasTranslations;

class Topic extends Model
{
    use Sluggable;
    use HasTranslations;
    public $translatable = ['name'];

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }

    /**
     * get list of questions
     * @return HasManyThrough
     */
    public function questions(): HasManyThrough
    {
        return $this->hasManyThrough(
            Question::class,
            QuestionTopic::class,
            'topic_id',
            'id',
            'id',
            'question_id'
        );
    }
}
