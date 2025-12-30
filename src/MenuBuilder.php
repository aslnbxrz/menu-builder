<?php

namespace Aslnbxrz\MenuBuilder;

use Aslnbxrz\MenuBuilder\Enums\MenuItemType;
use Aslnbxrz\MenuBuilder\Models\Menu;
use Aslnbxrz\MenuBuilder\Models\MenuItem;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class MenuBuilder
{
    protected string $menuTable;

    protected string $menuItemTable;

    protected string $cacheKey;

    protected int $cacheTtl;

    public function __construct()
    {
        $this->menuTable = config('menu-builder.menu.table', 'menus');
        $this->menuItemTable = config('menu-builder.menu_item.table', 'menu_items');
        $this->cacheKey = config('menu-builder.cache.key', 'menu:tree:');
        $this->cacheTtl = (int) config('menu-builder.cache.ttl', 360);
    }

    /* -------------------------------------------------
    | Public API
    ------------------------------------------------- */

    public function getMenu(string $alias): ?Menu
    {
        return Menu::query()->alias($alias)->active()->first();
    }

    /**
     * Frontend-ready TREE
     */
    public function getTree(string $menuAlias, ?User $user = null): array
    {
        return Cache::remember($this->cacheKey.$menuAlias, now()->addMinutes($this->cacheTtl), function () use ($menuAlias, $user) {
            $flat = $this->getFlatTree($menuAlias);

            $tree = $this->buildTree($flat);

            return $this->filterVisible($tree, $user);
        });
    }

    /**
     * Flat recursive tree (DB level)
     */
    public function getFlatTree(string $menuAlias): array
    {
        $menu = $this->getMenu($menuAlias);

        if (! $menu) {
            return [];
        }

        // Use Eloquent for database-agnostic approach
        $items = MenuItem::query()
            ->where('menu_id', $menu->id)
            ->where('is_active', true)
            ->orderBy('sort')
            ->get();

        $result = [];
        $this->buildFlatTreeRecursive($items, null, 0, '', $result);

        return $result;
    }

    /**
     * Recursively build flat tree structure
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, MenuItem>  $items
     * @param  array<int, object>  $result
     */
    protected function buildFlatTreeRecursive($items, ?int $parentId, int $depth, string $path, array &$result): void
    {
        foreach ($items as $item) {
            if ($item->parent_id === $parentId) {
                $currentPath = $path ? $path.'.'.$item->id : (string) $item->id;
                $url = trim(implode('/', array_filter([$item->link, $item->menuable_value])), '/');

                $itemData = (object) [
                    'id' => $item->id,
                    'menu_id' => $item->menu_id,
                    'parent_id' => $item->parent_id,
                    'menuable_type' => $item->menuable_type,
                    'menuable_id' => $item->menuable_id,
                    'menuable_value' => $item->menuable_value,
                    'title' => $item->title,
                    'link' => $item->link,
                    'type' => $item->type->value,
                    'is_active' => $item->is_active,
                    'sort' => $item->sort,
                    'meta' => $item->meta,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'depth' => $depth,
                    'path' => $currentPath,
                    'url' => $url,
                ];

                $result[] = $itemData;
                $this->buildFlatTreeRecursive($items, $item->id, $depth + 1, $currentPath, $result);
            }
        }
    }

    public static function clearCache(string $menuAlias): void
    {
        $key = config('menu-builder.cache.key', 'menu:tree:');
        Cache::forget($key.$menuAlias);
    }

    /* -------------------------------------------------
    | Internal helpers
    ------------------------------------------------- */

    protected function buildTree(array $items): array
    {
        $map = [];
        $tree = [];

        foreach ($items as $item) {
            $item->children = [];
            $map[$item->id] = $item;
        }

        foreach ($items as $item) {
            if ($item->parent_id && isset($map[$item->parent_id])) {
                $map[$item->parent_id]->children[] = $item;
            } else {
                $tree[] = $item;
            }
        }

        return $tree;
    }

    protected function filterVisible(array $items, ?User $user): array
    {
        return array_values(array_filter(array_map(
            fn ($item) => $this->filterItem($item, $user),
            $items
        )));
    }

    protected function filterItem(object $item, ?User $user): ?object
    {
        $item->children = $this->filterVisible($item->children, $user);

        if ($this->isVisible($item, $user) || count($item->children)) {
            return $item;
        }

        return null;
    }

    protected function isVisible(object $item, ?User $user): bool
    {
        if (! isset($item->type) || empty($item->type)) {
            return false;
        }

        try {
            $type = MenuItemType::from($item->type);
        } catch (\ValueError $e) {
            return false;
        }

        $meta = (array) ($item->meta ?? []);

        return match ($type) {
            MenuItemType::Url, MenuItemType::Divider => true,

            MenuItemType::Route => isset($meta['route']) && Route::has($meta['route']),

            MenuItemType::Permission => $user?->can($meta['permission'] ?? '') ?? false,

            MenuItemType::Feature => app()->bound('features')
                ? app('features')->active($meta['feature'] ?? '')
                : true,
        };
    }
}
