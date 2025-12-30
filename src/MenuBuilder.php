<?php

namespace Aslnbxrz\MenuBuilder;

use Aslnbxrz\MenuBuilder\Enums\MenuItemType;
use Aslnbxrz\MenuBuilder\Models\Menu;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class MenuBuilder
{
    protected string $menuTable;
    protected string $menuItemTable;
    protected string $cacheKey;
    protected int $cacheTtl;

    public function __construct()
    {
        $this->menuTable = config('menu-builder.menu.table');
        $this->menuItemTable = config('menu-builder.menu_item.table');
        $this->cacheKey = config('menu-builder.cache.key');
        $this->cacheTtl = (int)config('menu-builder.cache.ttl');
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
        return Cache::remember($this->cacheKey . $menuAlias, now()->addMinutes($this->cacheTtl), function () use ($menuAlias, $user) {
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

        if (!$menu) {
            return [];
        }

        return DB::select("
        WITH RECURSIVE menu_tree AS (
            SELECT
                mi.*,
                0 AS depth,
                mi.id::text AS path,
                concat_ws('/', mi.link, mi.menuable_value) AS url
            FROM {$this->menuItemTable} mi
            WHERE mi.menu_id = ?
              AND mi.parent_id IS NULL
              AND mi.is_active = true

            UNION ALL

            SELECT
                c.*,
                p.depth + 1,
                p.path || '.' || c.id,
                concat_ws('/', c.link, c.menuable_value) AS url
            FROM {$this->menuItemTable} c
            JOIN menu_tree p ON p.id = c.parent_id
            WHERE c.is_active = true
        )
        SELECT *
        FROM menu_tree
        ORDER BY sort
", [$menu->id]);
    }

    public static function clearCache(string $menuAlias): void
    {
        $key = config('menu-builder.cache.key', 60);
        Cache::forget($key . $menuAlias);
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
            if ($item->parent_id) {
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
            fn($item) => $this->filterItem($item, $user),
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
        $type = MenuItemType::from($item->type);
        $meta = (array)($item->meta ?? []);

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
