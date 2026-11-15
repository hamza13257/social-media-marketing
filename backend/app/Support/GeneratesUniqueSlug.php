<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait GeneratesUniqueSlug
{
    /**
     * @param  array<string, mixed>  $scope
     */
    protected function uniqueSlugFor(
        Model $model,
        string $base,
        string $column = 'slug',
        array $scope = [],
    ): string {
        $slug = Str::slug($base);
        if ($slug === '') {
            $slug = 'item';
        }

        $original = $slug;
        $counter = 1;

        while ($this->slugExists($model, $column, $slug, $scope)) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $scope
     */
    private function slugExists(Model $model, string $column, string $slug, array $scope): bool
    {
        $query = $model->newQuery()->where($column, $slug);

        foreach ($scope as $key => $value) {
            $query->where($key, $value);
        }

        if ($model->exists) {
            $query->where($model->getKeyName(), '!=', $model->getKey());
        }

        return $query->exists();
    }
}