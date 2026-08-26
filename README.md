# A simple package for managing Laravel token replacement from model data

[![Latest Version on Packagist](https://img.shields.io/packagist/v/eighteen73/laravel-tokens.svg?style=flat-square)](https://packagist.org/packages/eighteen73/laravel-tokens)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/eighteen73/laravel-tokens/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/eighteen73/laravel-tokens/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/eighteen73/laravel-tokens/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/eighteen73/laravel-tokens/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/eighteen73/laravel-tokens.svg?style=flat-square)](https://packagist.org/packages/eighteen73/laravel-tokens)

This package provides an automated way to replace tokens in user-entered text with model/relation data. This is useful for things like email templates.

Model attributes are available as tokens - as well as relation data accessed through dot notation. Any attribute listed in the model's `$hidden` is excluded, and `$appends` accessors are included.

For a model that exists in the database, `Model::getAttributes()` is used. For a model that doesn't yet exist, the factory definition keys are used when a factory is available, otherwise `Model::getFillable()`.

## Installation

You can install the package via composer:

```bash
composer require eighteen73/laravel-tokens
```


## Usage

### List available tokens

`plainTokens()` returns an array of the tokens available for a model, including
tokens for its `BelongsTo`/`HasOne` relations (dot notation) and its
`HasMany`/`BelongsToMany` relations (dot notation with a numeric index).

```php
use Eighteen73\LaravelTokens\TokenManager;

$tokenManager = new TokenManager();

print_r($tokenManager->forModel(App\Models\User::class)->plainTokens());

// [
//     '##name##',
//     '##posts.0.title##',
//     '##category.name##',
// ]
```

Relations are traversed to a depth of 2 by default. You can change or disable
this:

```php
$tokenManager->forModel(App\Models\User::class)->maxDepth(1)->plainTokens();
$tokenManager->forModel(App\Models\User::class)->withoutRelationships()->plainTokens();
```

### Replace tokens in a string

```php
use Eighteen73\LaravelTokens\TokenManager;

$tokenManager = new TokenManager();

echo $tokenManager->forModel(User::factory()->make(['email' => 'test@example.com']))
    ->replaceTokens("My email address is ##email##.");

// My email address is test@example.com
```

Tokens for relations are resolved from the model too:

```php
echo $tokenManager->forModel($user)
    ->replaceTokens("My first post is ##posts.0.title## in ##category.name##.");
```

### Custom tokens within a model

Implement the `CustomTokens` contract. `getCustomTokens()` returns a list of
token *names*, and `replaceCustomToken()` returns the value for a given name.

```php
use Eighteen73\LaravelTokens\Contracts\CustomTokens;

class User extends Model implements CustomTokens
{
    public function getCustomTokens(): array
    {
        return [
            'my_custom_token',
        ];
    }

    public function replaceCustomToken(string $token): string
    {
        return match ($token) {
            'my_custom_token' => 'Use this custom text.',
        };
    }
}

$tokenManager = new Eighteen73\LaravelTokens\TokenManager();

echo $tokenManager->forModel(User::factory()->make())
    ->replaceTokens("Example text - ##my_custom_token##.");

// Example text - Use this custom text.
```

### Facade

The `Tokens` facade resolves the same `TokenManager` out of the container:

```php
use Eighteen73\LaravelTokens\Facades\Tokens;

echo Tokens::forModel($user)->replaceTokens("Hello ##name##.");
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Matt Jones](https://github.com/Muffinman)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
