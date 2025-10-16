<?php

namespace Eighteen73\LaravelTokens\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Eighteen73\LaravelTokens\TokenManager
 */
class Tokens extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Eighteen73\LaravelTokens\TokenManager::class;
    }
}
