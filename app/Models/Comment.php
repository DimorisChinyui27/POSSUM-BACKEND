<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Comment extends Model
{
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function commented(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeApprovedComments(Builder $query): Builder
    {
        return $query->where('approved', true);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commented');
    }


}
