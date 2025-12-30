<?php

namespace Aslnbxrz\MenuBuilder\Models;

use Aslnbxrz\MenuBuilder\Enums\MenuItemType;
use Aslnbxrz\MenuBuilder\Observers\MenuItemObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[ObservedBy(MenuItemObserver::class)]
class MenuItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'type' => MenuItemType::class,
        'meta' => 'array',
    ];

    public function getTable()
    {
        return config('menu-builder.menu_item.table');
    }

    public function menuable(): MorphTo
    {
        return $this->morphTo();
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->scopes('ordered');
    }

    #[Scope]
    protected function root(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('sort');
    }

    #[Scope]
    protected function forMenu(Builder $query, string $alias): Builder
    {
        return $query->whereHas('menu', fn ($menu) => $menu->where('alias', $alias));
    }
}
