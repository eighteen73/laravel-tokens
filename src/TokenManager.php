<?php

namespace Eighteen73\LaravelTokens;

use Eighteen73\LaravelTokens\Contracts\CustomTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TokenManager
{
    protected ?Model $model = null;

    protected ?string $modelType = null;

    protected bool $withRelationships = true;

    protected int $maxDepth = 2;

    protected int $currentDepth = 0;

    protected array $relationPath = [];

    public function __construct(?Model $model = null)
    {
        if ($model) {
            $this->forModel($model);
        }
    }

    public function forModel(string|Model $model): self
    {
        if ($model instanceof Model) {
            $this->model = $model;
            $modelName = get_class($model);
        } else {
            $this->model = app($model);
            $modelName = $model;
        }

        $this->modelType = $modelName;

        return $this;
    }

    public function withoutRelationships(): self
    {
        $this->withRelationships = false;

        return $this;
    }

    public function maxDepth(int $depth): self
    {
        $this->maxDepth = $depth;

        return $this;
    }

    public function setDepth(int $depth): self
    {
        $this->currentDepth = $depth;

        return $this;
    }

    public function setRelationPath(array $relationPath): self
    {
        $this->relationPath = $relationPath;

        return $this;
    }

    protected function rawTokens(): Collection
    {
        if (! $this->modelType || ! class_exists($this->modelType)) {
            throw new \RuntimeException('Please call TokenManager::forModel() before attempting to load tokens');
        }

        $model = $this->model;

        if (! $model || ! $model->exists) {
            $model = app($this->modelType);

            if (method_exists($this->modelType, 'factory')) {
                $available = array_keys($this->modelType::factory()->definition());
            } else {
                $available = $model->getFillable();
            }
        } else {
            $available = array_keys($model->getAttributes());
        }

        $hidden = $model->getHidden();

        if ($model instanceof CustomTokens) {
            array_push($available, ...$model->getCustomTokens());
        }

        $attributes = collect($available)->push(...$model->getAppends())
            ->reject(fn ($value) => in_array($value, $hidden))
            ->map(fn ($item) => implode('.', [...$this->relationPath, $item]));

        if ($this->withRelationships && $this->currentDepth < $this->maxDepth) {
            $this->appendRelationshipTokens($attributes);
        }

        return $attributes;
    }

    public function plainTokens(): array
    {
        $attributes = $this->rawTokens()
            ->map(fn ($item) => '##'.$item.'##');

        return $attributes->toArray();
    }

    protected function appendRelationshipTokens(Collection $attributes): Collection
    {
        $model = $this->model ?? app($this->modelType);

        $methods = (new \ReflectionClass($model))->getMethods();

        // TODO: Need to decide what type of relations to show here
        foreach ($methods as $method) {
            if ($method->isPublic() && in_array($method->getReturnType(), [BelongsTo::class, HasOne::class])) {
                $relation = $model->{$method->getName()}();
                $related = $relation->getRelated();
                $relationTokens = (new self($related))
                    ->setRelationPath([...$this->relationPath, $method->getName()])
                    ->setDepth($this->currentDepth + 1)
                    ->rawTokens();
                $attributes->push(...$relationTokens);
            }
            if ($method->isPublic() && in_array($method->getReturnType(), [HasMany::class, BelongsToMany::class])) {
                $relation = $model->{$method->getName()}();
                $related = $relation->getRelated();
                $relationTokens = (new self($related))
                    ->setRelationPath([...$this->relationPath, $method->getName(), '0'])
                    ->setDepth($this->currentDepth + 1)
                    ->rawTokens();
                $attributes->push(...$relationTokens);
            }
        }

        return $attributes;
    }

    public function replaceTokens(string $text): string
    {
        if (! $this->model) {
            return $text;
        }

        // Look up all valid tokens
        $validTokens = $this->rawTokens();

        // preg_match all possible tokens
        preg_match_all('/##([A-Z0-9_.]+)##/i', $text, $tokens);

        // Fetch all relations we might need. Discard any matching model name because those are direct lookups
        $relations = array_filter($tokens[1], fn ($token) => Str::contains($token, '.'));
        $relations = array_map(function (string $relation) {
            return Str::before($relation, '.');
        }, $relations);
        $relations = array_unique($relations);

        $this->model->loadMissing(array_unique($relations));

        // Check for token matches
        if (! empty($tokens[0])) {
            // This var contains matches without the ## which saves us stripping them
            $tokensToReplace = array_intersect($validTokens->toArray(), $tokens[1]);

            foreach ($tokensToReplace as $token) {
                $relation = null;
                $attribute = $token;
                $index = null;

                if (Str::contains($token, '.')) {
                    $relation = Str::beforeLast($token, '.');
                    $attribute = Str::afterLast($token, '.');
                }

                // Lookup value
                $model = $this->model;

                if ($relation) {
                    foreach (explode('.', $relation) as $relationName) {
                        // Handle .0. syntax - pull the index from the relation / collection
                        if (is_numeric($relationName) && ($model instanceof HasMany || $model instanceof BelongsToMany || $model instanceof Collection)) {
                            $model = $model->values()->get($relationName);
                        } else {
                            $model = $model->getRelation($relationName);
                        }
                    }
                }

                // What if the model was deleted for some reason?
                if (! $model) {
                    continue;
                }

                $customTokens = [];
                if ($model instanceof CustomTokens) {
                    $customTokens = $model->getCustomTokens();
                }

                if (in_array($attribute, $customTokens)) {
                    $value = $model->replaceCustomToken($attribute);
                } else {
                    $value = $model->getAttribute($attribute);
                }

                // preg_replace all ones we have value for
                $text = str_replace('##'.$token.'##', $value, $text);
            }
        }

        return $text;
    }
}
