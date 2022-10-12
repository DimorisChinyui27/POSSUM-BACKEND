<?php
declare(strict_types=1);

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Commentable
{
    public function comments(): MorphMany;

    public function canBeRated(): bool;

    public function mustBeApproved(): bool;

    public function primaryId(): string;
}
