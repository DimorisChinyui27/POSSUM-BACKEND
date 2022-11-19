<?php
namespace App\Interface;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Commentable
{
    public function comments(): MorphMany;
    public function primaryId(): string;
}
