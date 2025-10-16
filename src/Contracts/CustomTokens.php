<?php

namespace Eighteen73\LaravelTokens\Contracts;

interface CustomTokens
{
    public function getCustomTokens(): array;

    public function replaceCustomToken(string $token): string;
}
