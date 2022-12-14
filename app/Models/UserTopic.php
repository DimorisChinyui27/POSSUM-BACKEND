<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class UserTopic extends Model
{
    protected $table='users_topics';
    protected $fillable = ['user_id', 'rating', 'confidence_score', 'topic_id'];
}
