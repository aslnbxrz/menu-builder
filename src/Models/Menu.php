<?php

namespace Aslnbxrz\MenuBuilder\Models;

use Aslnbxrz\MenuBuilder\Observers\MenuObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(MenuObserver::class)]
class Menu extends Model
{
    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
    ];

    public function getTable()
    {
        return config('menu-builder.menu.table');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function alias(Builder $query, string $alias): Builder
    {
        return $query->where('alias', $alias);
    }
}
