<?php

use Aslnbxrz\MenuBuilder\Enums\MenuItemType;
use Aslnbxrz\MenuBuilder\Models\Menu;
use Aslnbxrz\MenuBuilder\Models\MenuItem;
use Illuminate\Support\Facades\Route;

describe('MenuItem Model', function () {
    beforeEach(function () {
        $this->menu = Menu::create([
            'alias' => 'test-menu',
            'title' => 'Test Menu',
            'is_active' => true,
        ]);
    });

    test('can create menu item', function () {
        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Test Item',
            'link' => '/test',
            'type' => MenuItemType::Url,
            'sort' => 1,
            'is_active' => true,
        ]);

        expect($item)->toBeInstanceOf(MenuItem::class)
            ->and($item->menu_id)->toBe($this->menu->id)
            ->and($item->title)->toBe('Test Item')
            ->and($item->link)->toBe('/test')
            ->and($item->type)->toBe(MenuItemType::Url)
            ->and($item->sort)->toBe(1)
            ->and($item->is_active)->toBeTrue();
    });

    test('can create nested menu item', function () {
        $parent = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Parent',
            'link' => '/parent',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        $child = MenuItem::create([
            'menu_id' => $this->menu->id,
            'parent_id' => $parent->id,
            'title' => 'Child',
            'link' => '/parent/child',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        expect($child->parent_id)->toBe($parent->id)
            ->and($child->parent->id)->toBe($parent->id);
    });

    test('can update menu item', function () {
        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Original',
            'link' => '/original',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        $item->update(['title' => 'Updated']);

        expect($item->fresh()->title)->toBe('Updated');
    });

    test('can delete menu item', function () {
        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Test',
            'link' => '/test',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        $item->delete();

        expect(MenuItem::find($item->id))->toBeNull();
    });
});

describe('MenuItem Types', function () {
    beforeEach(function () {
        $this->menu = Menu::create([
            'alias' => 'test-menu',
            'title' => 'Test Menu',
            'is_active' => true,
        ]);
    });

    test('can create URL type item', function () {
        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'URL Item',
            'link' => '/url',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        expect($item->type)->toBe(MenuItemType::Url);
    });

    test('can create Route type item', function () {
        Route::get('/test-route', fn () => 'test')->name('test.route');

        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Route Item',
            'type' => MenuItemType::Route,
            'meta' => ['route' => 'test.route'],
            'is_active' => true,
        ]);

        expect($item->type)->toBe(MenuItemType::Route)
            ->and($item->meta['route'])->toBe('test.route');
    });

    test('can create Permission type item', function () {
        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Admin',
            'link' => '/admin',
            'type' => MenuItemType::Permission,
            'meta' => ['permission' => 'access-admin'],
            'is_active' => true,
        ]);

        expect($item->type)->toBe(MenuItemType::Permission)
            ->and($item->meta['permission'])->toBe('access-admin');
    });

    test('can create Feature type item', function () {
        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Beta Feature',
            'link' => '/beta',
            'type' => MenuItemType::Feature,
            'meta' => ['feature' => 'beta-feature'],
            'is_active' => true,
        ]);

        expect($item->type)->toBe(MenuItemType::Feature)
            ->and($item->meta['feature'])->toBe('beta-feature');
    });

    test('can create Divider type item', function () {
        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'type' => MenuItemType::Divider,
            'is_active' => true,
        ]);

        expect($item->type)->toBe(MenuItemType::Divider)
            ->and($item->title)->toBeNull()
            ->and($item->link)->toBeNull();
    });
});

describe('MenuItem Relationships', function () {
    beforeEach(function () {
        $this->menu = Menu::create([
            'alias' => 'test-menu',
            'title' => 'Test Menu',
            'is_active' => true,
        ]);
    });

    test('belongs to menu', function () {
        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Test',
            'link' => '/test',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        expect($item->menu)->toBeInstanceOf(Menu::class)
            ->and($item->menu->id)->toBe($this->menu->id);
    });

    test('belongs to parent item', function () {
        $parent = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Parent',
            'link' => '/parent',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        $child = MenuItem::create([
            'menu_id' => $this->menu->id,
            'parent_id' => $parent->id,
            'title' => 'Child',
            'link' => '/child',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        expect($child->parent)->toBeInstanceOf(MenuItem::class)
            ->and($child->parent->id)->toBe($parent->id);
    });

    test('has many children', function () {
        $parent = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Parent',
            'link' => '/parent',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        $child1 = MenuItem::create([
            'menu_id' => $this->menu->id,
            'parent_id' => $parent->id,
            'title' => 'Child 1',
            'link' => '/child-1',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        $child2 = MenuItem::create([
            'menu_id' => $this->menu->id,
            'parent_id' => $parent->id,
            'title' => 'Child 2',
            'link' => '/child-2',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        expect($parent->children)->toHaveCount(2)
            ->and($parent->children->first()->title)->toBe('Child 1')
            ->and($parent->children->last()->title)->toBe('Child 2');
    });

    test('root item has no parent', function () {
        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Root',
            'link' => '/root',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        expect($item->parent_id)->toBeNull()
            ->and($item->parent)->toBeNull();
    });
});

describe('MenuItem Scopes', function () {
    beforeEach(function () {
        $this->menu = Menu::create([
            'alias' => 'test-menu',
            'title' => 'Test Menu',
            'is_active' => true,
        ]);

        // Root items
        $this->root1 = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Root 1',
            'link' => '/root-1',
            'type' => MenuItemType::Url,
            'sort' => 2,
            'is_active' => true,
        ]);

        $this->root2 = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Root 2',
            'link' => '/root-2',
            'type' => MenuItemType::Url,
            'sort' => 1,
            'is_active' => false,
        ]);

        // Child item
        $this->child = MenuItem::create([
            'menu_id' => $this->menu->id,
            'parent_id' => $this->root1->id,
            'title' => 'Child',
            'link' => '/child',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);
    });

    test('root scope returns only root items', function () {
        $rootItems = MenuItem::root()->get();

        expect($rootItems)->toHaveCount(2)
            ->and($rootItems->pluck('parent_id')->unique())->toHaveCount(1)
            ->and($rootItems->pluck('parent_id')->first())->toBeNull();
    });

    test('active scope returns only active items', function () {
        $activeItems = MenuItem::active()->get();

        expect($activeItems)->toHaveCount(2)
            ->and($activeItems->every(fn ($item) => $item->is_active))->toBeTrue();
    });

    test('ordered scope sorts by sort field', function () {
        $items = MenuItem::root()->ordered()->get();

        expect($items->first()->title)->toBe('Root 2') // sort: 1
            ->and($items->last()->title)->toBe('Root 1'); // sort: 2
    });

    test('forMenu scope filters by menu alias', function () {
        $otherMenu = Menu::create([
            'alias' => 'other-menu',
            'title' => 'Other Menu',
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $otherMenu->id,
            'title' => 'Other Item',
            'link' => '/other',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        $items = MenuItem::forMenu('test-menu')->get();

        expect($items)->toHaveCount(3) // 2 root + 1 child
            ->and($items->every(fn ($item) => $item->menu_id === $this->menu->id))->toBeTrue();
    });

    test('can chain multiple scopes', function () {
        $items = MenuItem::active()->root()->ordered()->get();

        expect($items)->toHaveCount(1)
            ->and($items->first()->title)->toBe('Root 1');
    });
});

describe('MenuItem Fields', function () {
    beforeEach(function () {
        $this->menu = Menu::create([
            'alias' => 'test-menu',
            'title' => 'Test Menu',
            'is_active' => true,
        ]);
    });

    test('meta field stores JSON data', function () {
        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Test',
            'link' => '/test',
            'type' => MenuItemType::Url,
            'meta' => [
                'icon' => 'home',
                'badge' => 'new',
                'attributes' => ['target' => '_blank'],
            ],
            'is_active' => true,
        ]);

        $meta = $item->fresh()->meta;

        expect($meta)->toBeArray()
            ->and($meta['icon'])->toBe('home')
            ->and($meta['badge'])->toBe('new')
            ->and($meta['attributes']['target'])->toBe('_blank');
    });

    test('sort field defaults to 0', function () {
        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Test',
            'link' => '/test',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        expect($item->sort)->toBe(0);
    });

    test('is_active defaults to true', function () {
        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Test',
            'link' => '/test',
            'type' => MenuItemType::Url,
        ]);

        expect($item->is_active)->toBeTrue();
    });
});

describe('MenuItem Deep Nesting', function () {
    beforeEach(function () {
        $this->menu = Menu::create([
            'alias' => 'test-menu',
            'title' => 'Test Menu',
            'is_active' => true,
        ]);
    });

    test('can create deeply nested structure', function () {
        $level1 = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Level 1',
            'link' => '/l1',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        $level2 = MenuItem::create([
            'menu_id' => $this->menu->id,
            'parent_id' => $level1->id,
            'title' => 'Level 2',
            'link' => '/l1/l2',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        $level3 = MenuItem::create([
            'menu_id' => $this->menu->id,
            'parent_id' => $level2->id,
            'title' => 'Level 3',
            'link' => '/l1/l2/l3',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        expect($level3->parent->parent->id)->toBe($level1->id)
            ->and($level1->children->first()->children->first()->id)->toBe($level3->id);
    });
});
