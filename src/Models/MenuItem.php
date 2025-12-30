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

/**
 * @property int $id
 * @property int $menu_id
 * @property int|null $parent_id
 * @property string|null $menuable_type
 * @property int|null $menuable_id
 * @property string|null $menuable_value
 * @property string|null $title
 * @property string|null $link
 * @property MenuItemType $type
 * @property bool $is_active
 * @property int $sort
 * @property array|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
#[ObservedBy(MenuItemObserver::class)]
class MenuItem extends Model
{
    protected $guarded = [];

    protected $attributes = [
        'is_active' => true,
        'sort' => 0,
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort' => 'integer',
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
