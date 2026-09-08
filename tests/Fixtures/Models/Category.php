<?php

namespace Eighteen73\LaravelTokens\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    protected $fillable = ['name'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
