<?php

use Aslnbxrz\MenuBuilder\Enums\MenuItemType;
use Aslnbxrz\MenuBuilder\Models\Menu;
use Aslnbxrz\MenuBuilder\Models\MenuItem;

describe('Menu Model', function () {
    test('can create menu', function () {
        $menu = Menu::create([
            'alias' => 'test-menu',
            'title' => 'Test Menu',
            'description' => 'Test Description',
            'is_active' => true,
        ]);

        expect($menu)->toBeInstanceOf(Menu::class)
            ->and($menu->alias)->toBe('test-menu')
            ->and($menu->title)->toBe('Test Menu')
            ->and($menu->description)->toBe('Test Description')
            ->and($menu->is_active)->toBeTrue();
    });

    test('can update menu', function () {
        $menu = Menu::create([
            'alias' => 'test-menu',
            'title' => 'Original Title',
            'is_active' => true,
        ]);

        $menu->update(['title' => 'Updated Title']);

        expect($menu->fresh()->title)->toBe('Updated Title');
    });

    test('can delete menu', function () {
        $menu = Menu::create([
            'alias' => 'test-menu',
            'title' => 'Test Menu',
            'is_active' => true,
        ]);

        $menu->delete();

        expect(Menu::find($menu->id))->toBeNull();
    });

    test('has timestamps', function () {
        $menu = Menu::create([
            'alias' => 'test-menu',
            'title' => 'Test Menu',
            'is_active' => true,
        ]);

        expect($menu->created_at)->not->toBeNull()
            ->and($menu->updated_at)->not->toBeNull();
    });
});

describe('Menu Relationships', function () {
    test('has many items', function () {
        $menu = Menu::create([
            'alias' => 'test-menu',
            'title' => 'Test Menu',
            'is_active' => true,
        ]);

        $item1 = MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Item 1',
            'link' => '/item-1',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        $item2 = MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Item 2',
            'link' => '/item-2',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        expect($menu->items)->toHaveCount(2)
            ->and($menu->items->first()->title)->toBe('Item 1')
            ->and($menu->items->last()->title)->toBe('Item 2');
    });

    test('deleting menu does not delete items by default', function () {
        $menu = Menu::create([
            'alias' => 'test-menu',
            'title' => 'Test Menu',
            'is_active' => true,
        ]);

        $item = MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Item',
            'link' => '/item',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        $itemId = $item->id;
        $menu->delete();

        // Item should still exist (no cascade delete by default)
        expect(MenuItem::find($itemId))->not->toBeNull();
    });
});

describe('Menu Scopes', function () {
    beforeEach(function () {
        Menu::create([
            'alias' => 'active-menu',
            'title' => 'Active Menu',
            'is_active' => true,
        ]);

        Menu::create([
            'alias' => 'inactive-menu',
            'title' => 'Inactive Menu',
            'is_active' => false,
        ]);
    });

    test('active scope returns only active menus', function () {
        $activeMenus = Menu::active()->get();

        expect($activeMenus)->toHaveCount(1)
            ->and($activeMenus->first()->alias)->toBe('active-menu');
    });

    test('alias scope finds menu by alias', function () {
        $menu = Menu::alias('active-menu')->first();

        expect($menu)->not->toBeNull()
            ->and($menu->alias)->toBe('active-menu');
    });

    test('alias scope returns null for non-existent alias', function () {
        $menu = Menu::alias('non-existent')->first();

        expect($menu)->toBeNull();
    });

    test('can chain scopes', function () {
        $menu = Menu::active()->alias('active-menu')->first();

        expect($menu)->not->toBeNull()
            ->and($menu->alias)->toBe('active-menu')
            ->and($menu->is_active)->toBeTrue();
    });
});

describe('Menu Validation', function () {
    test('alias is required', function () {
        expect(fn () => Menu::create([
            'title' => 'Test Menu',
            'is_active' => true,
        ]))->toThrow(Exception::class);
    });

    test('can have nullable title', function () {
        $menu = Menu::create([
            'alias' => 'test-menu',
            'is_active' => true,
        ]);

        expect($menu->title)->toBeNull();
    });

    test('can have nullable description', function () {
        $menu = Menu::create([
            'alias' => 'test-menu',
            'is_active' => true,
        ]);

        expect($menu->description)->toBeNull();
    });

    test('is_active defaults to true', function () {
        $menu = Menu::create([
            'alias' => 'test-menu',
            'title' => 'Test Menu',
        ]);

        expect($menu->is_active)->toBeTrue();
    });
});

describe('Menu JSON Meta Field', function () {
    test('can store and retrieve meta data', function () {
        $menu = Menu::create([
            'alias' => 'test-menu',
            'title' => 'Test Menu',
            'is_active' => true,
            'meta' => [
                'icon' => 'menu-icon',
                'order' => 1,
                'settings' => [
                    'collapsible' => true,
                    'theme' => 'dark',
                ],
            ],
        ]);

        $meta = $menu->fresh()->meta;

        expect($meta)->toBeArray()
            ->and($meta['icon'])->toBe('menu-icon')
            ->and($meta['order'])->toBe(1)
            ->and($meta['settings']['collapsible'])->toBeTrue()
            ->and($meta['settings']['theme'])->toBe('dark');
    });

    test('meta can be null', function () {
        $menu = Menu::create([
            'alias' => 'test-menu',
            'title' => 'Test Menu',
            'is_active' => true,
        ]);

        expect($menu->meta)->toBeNull();
    });

    test('can update meta field', function () {
        $menu = Menu::create([
            'alias' => 'test-menu',
            'title' => 'Test Menu',
            'is_active' => true,
            'meta' => ['key' => 'value'],
        ]);

        $menu->update([
            'meta' => ['key' => 'updated', 'new_key' => 'new_value'],
        ]);

        $meta = $menu->fresh()->meta;

        expect($meta['key'])->toBe('updated')
            ->and($meta['new_key'])->toBe('new_value');
    });
});

describe('Menu Casts', function () {
    test('is_active is cast to boolean', function () {
        $menu = Menu::create([
            'alias' => 'test-menu',
            'title' => 'Test Menu',
            'is_active' => 1,
        ]);

        expect($menu->is_active)->toBeTrue()
            ->and($menu->is_active)->toBeBool();
    });

    test('meta is cast to array', function () {
        $menu = Menu::create([
            'alias' => 'test-menu',
            'title' => 'Test Menu',
            'is_active' => true,
            'meta' => ['test' => 'value'],
        ]);

        expect($menu->meta)->toBeArray();
    });
});
