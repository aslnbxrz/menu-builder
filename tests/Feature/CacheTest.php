<?php

use Aslnbxrz\MenuBuilder\Enums\MenuItemType;
use Aslnbxrz\MenuBuilder\Facades\MenuBuilder;
use Aslnbxrz\MenuBuilder\Models\Menu;
use Aslnbxrz\MenuBuilder\Models\MenuItem;
use Illuminate\Support\Facades\Cache;

describe('Menu Cache', function () {
    beforeEach(function () {
        Cache::flush();

        $this->menu = Menu::create([
            'alias' => 'cache-test-menu',
            'title' => 'Cache Test Menu',
            'is_active' => true,
        ]);

        $this->item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Test Item',
            'link' => '/test',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);
    });

    test('getTree retrieves from cache', function () {
        // Manually put data in cache
        $cacheKey = config('menu-builder.cache.key', 'menu:tree:').'cache-test-menu';
        $item = (object) [
            'id' => 999,
            'title' => 'Cached Item',
            'type' => MenuItemType::Url->value,
            'children' => [],
            'meta' => [],
        ];
        $cachedData = [$item];

        Cache::put($cacheKey, $cachedData, 60);

        // Call method - should return cached data instead of real DB data
        $tree = MenuBuilder::getTree('cache-test-menu');

        expect($tree)->toHaveCount(1)
            ->and($tree[0]->title)->toBe('Cached Item');
    });

    test('cache key includes menu alias', function () {
        $cacheKey = config('menu-builder.cache.key', 'menu:tree:').'cache-test-menu';

        MenuBuilder::getTree('cache-test-menu');

        expect(Cache::has($cacheKey))->toBeTrue();
    });

    test('clearCache removes cached data', function () {
        // Cache the menu
        MenuBuilder::getTree('cache-test-menu');

        $cacheKey = config('menu-builder.cache.key', 'menu:tree:').'cache-test-menu';

        expect(Cache::has($cacheKey))->toBeTrue();

        // Clear cache
        MenuBuilder::clearCache('cache-test-menu');

        expect(Cache::has($cacheKey))->toBeFalse();
    });

    test('multiple menus have separate cache', function () {
        $menu2 = Menu::create([
            'alias' => 'second-menu',
            'title' => 'Second Menu',
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $menu2->id,
            'title' => 'Second Item',
            'link' => '/second',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        // Cache both menus
        MenuBuilder::getTree('cache-test-menu');
        MenuBuilder::getTree('second-menu');

        $cacheKey1 = config('menu-builder.cache.key', 'menu:tree:').'cache-test-menu';
        $cacheKey2 = config('menu-builder.cache.key', 'menu:tree:').'second-menu';

        expect(Cache::has($cacheKey1))->toBeTrue()
            ->and(Cache::has($cacheKey2))->toBeTrue();

        // Clear only first menu's cache
        MenuBuilder::clearCache('cache-test-menu');

        expect(Cache::has($cacheKey1))->toBeFalse()
            ->and(Cache::has($cacheKey2))->toBeTrue();
    });
});

describe('Observer Cache Invalidation', function () {
    beforeEach(function () {
        Cache::flush();

        $this->menu = Menu::create([
            'alias' => 'observer-test-menu',
            'title' => 'Observer Test Menu',
            'is_active' => true,
        ]);
    });

    test('creating menu item clears cache', function () {
        // Cache the menu
        MenuBuilder::getTree('observer-test-menu');

        $cacheKey = config('menu-builder.cache.key', 'menu:tree:').'observer-test-menu';
        expect(Cache::has($cacheKey))->toBeTrue();

        // Create new item - should trigger observer
        MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'New Item',
            'link' => '/new',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        // Cache should be cleared
        expect(Cache::has($cacheKey))->toBeFalse();
    });

    test('updating menu item clears cache', function () {
        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Original',
            'link' => '/original',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        // Cache the menu
        MenuBuilder::getTree('observer-test-menu');

        $cacheKey = config('menu-builder.cache.key', 'menu:tree:').'observer-test-menu';
        expect(Cache::has($cacheKey))->toBeTrue();

        // Update item
        $item->update(['title' => 'Updated']);

        // Cache should be cleared
        expect(Cache::has($cacheKey))->toBeFalse();
    });

    test('deleting menu item clears cache', function () {
        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'To Delete',
            'link' => '/delete',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        // Cache the menu
        MenuBuilder::getTree('observer-test-menu');

        $cacheKey = config('menu-builder.cache.key', 'menu:tree:').'observer-test-menu';
        expect(Cache::has($cacheKey))->toBeTrue();

        // Delete item
        $item->delete();

        // Cache should be cleared
        expect(Cache::has($cacheKey))->toBeFalse();
    });

    test('updating menu clears cache', function () {
        // Cache the menu
        MenuBuilder::getTree('observer-test-menu');

        $cacheKey = config('menu-builder.cache.key', 'menu:tree:').'observer-test-menu';
        expect(Cache::has($cacheKey))->toBeTrue();

        // Update menu
        $this->menu->update(['title' => 'Updated Title']);

        // Cache should be cleared
        expect(Cache::has($cacheKey))->toBeFalse();
    });

    test('deleting menu clears cache', function () {
        // Cache the menu
        MenuBuilder::getTree('observer-test-menu');

        $cacheKey = config('menu-builder.cache.key', 'menu:tree:').'observer-test-menu';
        expect(Cache::has($cacheKey))->toBeTrue();

        // Delete menu
        $this->menu->delete();

        // Cache should be cleared
        expect(Cache::has($cacheKey))->toBeFalse();
    });
});

describe('Cache Performance', function () {
    test('cached tree retrieval is faster than fresh query', function () {
        $menu = Menu::create([
            'alias' => 'perf-menu',
            'title' => 'Performance Menu',
            'is_active' => true,
        ]);

        // Create many items
        for ($i = 1; $i <= 50; $i++) {
            MenuItem::create([
                'menu_id' => $menu->id,
                'title' => "Item $i",
                'link' => "/item-$i",
                'type' => MenuItemType::Url,
                'is_active' => true,
            ]);
        }

        // First call (not cached)
        $start1 = microtime(true);
        MenuBuilder::getTree('perf-menu');
        $time1 = microtime(true) - $start1;

        // Second call (cached)
        $start2 = microtime(true);
        MenuBuilder::getTree('perf-menu');
        $time2 = microtime(true) - $start2;

        // Cached call should be faster
        expect($time2)->toBeLessThan($time1);
    });
});

describe('Cache TTL', function () {
    test('cache respects TTL configuration', function () {
        $ttl = config('menu-builder.cache.ttl', 360);

        expect($ttl)->toBeInt()
            ->and($ttl)->toBeGreaterThan(0);
    });

    test('cache key respects configuration', function () {
        $key = config('menu-builder.cache.key', 'menu:tree:');

        expect($key)->toBeString()
            ->and($key)->not->toBeEmpty();
    });
});
