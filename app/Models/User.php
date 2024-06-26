<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Khsing\World\Models\City;
use Khsing\World\Models\Country;
use Laratrust\Traits\LaratrustUserTrait;
use Laravel\Sanctum\HasApiTokens;
use LaravelInteraction\Vote\Concerns\Voter;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Voter;
    use HasApiTokens, HasFactory, Notifiable, LaratrustUserTrait;

    protected $table = 'users';

    protected $appends = ['img'];

    // Rest omitted for brevity
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * @return HasOne
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * @return HasMany
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * @return HasMany
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    /**
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function topics(): belongsToMany
    {
        return $this->belongsToMany(
            Topic::class,
            UserTopic::class,
            'user_id',
            'topic_id'


        );
    }

    /**
     * get country
     * @return BelongsTo
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class)->select(['id', 'name']);
    }

    /**
     * get city
     * @return BelongsTo
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class)->select(['id', 'name']);
    }

    /**
     * @return MorphMany
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commented');
    }

    public function comment($commentable, string $commentText = ''): Comment
    {
        $comment = new Comment();
        $comment->commentable()->associate($commentable);
        $comment->commented_id = $this->id;
        $comment->commented_type = User::class;
        $comment->comment = $commentText;
        $comment->save();
        return $comment;
    }

    public function getImgAttribute()
    {
        if (!empty($this->media)) {
            return asset('storage/files/users/' . $this->media->file_name);
        } else {
            return asset('images/avatar.jpeg');
        }
    }

    public function scopeTop($query)
    {
        return $query->withCount(['answers', 'questions'])->orderByDesc('answers_count')
            ->orderByDesc('questions_count')
            ->orderByDesc('created_at');
    }


    /**
     * @return MorphOne
     */
    public function media(): morphOne
    {
        return $this->morphOne(Media::class, 'mediable')->orderBy('file_size', 'asc');
    }
}
