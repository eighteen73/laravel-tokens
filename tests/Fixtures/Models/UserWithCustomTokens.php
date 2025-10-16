<?php

namespace Eighteen73\LaravelTokens\Tests\Fixtures\Models;

use Eighteen73\LaravelTokens\Contracts\CustomTokens;
use Illuminate\Database\Eloquent\Model;

class UserWithCustomTokens extends Model implements CustomTokens
{
    protected $fillable = ['name'];

    public function getCustomTokens(): array
    {
        return [
            'custom_token',
        ];
    }

    public function replaceCustomToken(string $token): string
    {
        return match ($token) {
            'custom_token' => 'TESTING123',
        };
    }
}
