<?php

namespace Eighteen73\LaravelTokens\Facades;

use Eighteen73\LaravelTokens\TokenManager;
use Illuminate\Support\Facades\Facade;

/**
 * @see TokenManager
 */
class Tokens extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TokenManager::class;
    }
}
