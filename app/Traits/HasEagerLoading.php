<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

trait HasEagerLoading
{
    /**
     * Boot the HasEagerLoading trait for a model.
     */
    public static function bootHasEagerLoading(): void
    {
        // Automatically eager load relationships when querying
        static::addGlobalScope('eagerLoad', function (Builder $builder): void {
            if (method_exists($builder->getModel(), 'getDefaultEagerLoads')) {
                $builder->with($builder->getModel()->getDefaultEagerLoads());
            }
        });
    }

    /**
     * Get the relationships that should be eager loaded by default.
     */
    public function getDefaultEagerLoads(): array
    {
        return property_exists($this, 'with') ? $this->with : [];
    }

    /**
     * Scope a query to eager load specific relationships.
     *
     * @param  array|string  $relations
     */
    public function scopeWithRelations(Builder $query, $relations): Builder
    {
        return $query->with($relations);
    }

    /**
     * Scope a query to count relationships without loading them.
     *
     * @param  array|string  $relations
     */
    public function scopeWithCount(Builder $query, $relations): Builder
    {
        return $query->withCount($relations);
    }

    /**
     * Scope a query to eager load relationships only if they exist.
     *
     * @param  array|string  $relations
     */
    public function scopeWithExists(Builder $query, $relations): Builder
    {
        return $query->withExists($relations);
    }

    /**
     * Load relationships if they haven't been loaded yet.
     *
     * @param  array|string  $relations
     * @return $this
     */
    public function loadIfNotLoaded($relations): self
    {
        $relations = is_string($relations) ? func_get_args() : $relations;

        foreach ($relations as $relation) {
            if (! $this->relationLoaded($relation)) {
                $this->load($relation);
            }
        }

        return $this;
    }

    /**
     * Get eager loadable relation names.
     */
    public function getEagerLoadableRelations(): array
    {
        $methods = get_class_methods($this);
        $relations = [];

        foreach ($methods as $method) {
            try {
                $reflection = new \ReflectionMethod($this, $method);

                // Skip magic methods, constructors, and private/protected methods
                if ($reflection->isPublic() &&
                    ! $reflection->isStatic() &&
                    ! $reflection->isAbstract() &&
                    !str_starts_with($method, '__') &&
                    $method !== 'getEagerLoadableRelations'
                ) {
                    $returnType = $reflection->getReturnType();

                    if ($returnType &&
                        is_a($returnType->getName(), Relation::class, true)
                    ) {
                        $relations[] = $method;
                    }
                }
            } catch (\ReflectionException) {
                continue;
            }
        }

        return $relations;
    }
}
