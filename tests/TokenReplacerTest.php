<?php

use Eighteen73\LaravelTokens\Tests\Fixtures\Models\Category;
use Eighteen73\LaravelTokens\Tests\Fixtures\Models\Post;
use Eighteen73\LaravelTokens\Tests\Fixtures\Models\User;
use Eighteen73\LaravelTokens\Tests\Fixtures\Models\UserWithCustomTokens;
use Eighteen73\LaravelTokens\TokenManager;

it('can generate list of tokens', function () {
    $tokens = app(TokenManager::class)->forModel(User::class)->plainTokens();

    expect($tokens)->toBeArray();
    expect($tokens)->not->tobeEmpty();
});

it('can replace custom tokens', function () {
    $tokens = app(TokenManager::class)->forModel(UserWithCustomTokens::class)->plainTokens();

    expect($tokens)->toContain('##custom_token##');

    $text = app(TokenManager::class)
        ->forModel(UserWithCustomTokens::class)
        ->replaceTokens('Hello ##custom_token##');

    expect($text)->toBe('Hello TESTING123');
});

it('can replace tokens in simple relations', function () {
    $tokens = app(TokenManager::class)->forModel(User::class)->plainTokens();

    expect($tokens)->toContain('##category.name##');

    $user = new User();
    $user->setRelation('category', new Category(['name' => 'TESTCATEGORY123']));

    $text = app(TokenManager::class)
        ->forModel($user)
        ->replaceTokens('Category ##category.name##');

    expect($text)->toBe('Category TESTCATEGORY123');

});

it('can replace tokens in many relations', function () {
    $tokens = app(TokenManager::class)->forModel(User::class)->plainTokens();

    expect($tokens)->toContain('##posts.0.title##');

    $user = new User();
    $user->setRelation('posts', collect(new Post(['title' => 'TESTPOST123'])));

    $text = app(TokenManager::class)
        ->forModel($user)
        ->replaceTokens('Post ##posts.0.title##');

    expect($text)->toBe('Post TESTPOST123');
});
