<?php

namespace Aslnbxrz\MenuBuilder\Models;

use Aslnbxrz\MenuBuilder\Observers\MenuObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string|null $title
 * @property string|null $description
 * @property string $alias
 * @property bool $is_active
 * @property array|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
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
