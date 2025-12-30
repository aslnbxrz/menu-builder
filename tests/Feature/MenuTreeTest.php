<?php

use Aslnbxrz\MenuBuilder\Enums\MenuItemType;
use Aslnbxrz\MenuBuilder\Facades\MenuBuilder;
use Aslnbxrz\MenuBuilder\Models\Menu;
use Aslnbxrz\MenuBuilder\Models\MenuItem;

describe('Menu Tree Building', function () {
    beforeEach(function () {
        $this->menu = Menu::create([
            'alias' => 'tree-test-menu',
            'title' => 'Tree Test Menu',
            'is_active' => true,
        ]);

        // Create structure:
        // Home
        //   ├─ Products
        //   │   ├─ Laptops
        //   │   └─ Phones
        //   └─ Services
        // About

        $this->home = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Home',
            'link' => '/',
            'type' => MenuItemType::Url,
            'sort' => 1,
            'is_active' => true,
        ]);

        $this->products = MenuItem::create([
            'menu_id' => $this->menu->id,
            'parent_id' => $this->home->id,
            'title' => 'Products',
            'link' => '/products',
            'type' => MenuItemType::Url,
            'sort' => 1,
            'is_active' => true,
        ]);

        $this->laptops = MenuItem::create([
            'menu_id' => $this->menu->id,
            'parent_id' => $this->products->id,
            'title' => 'Laptops',
            'link' => '/products/laptops',
            'type' => MenuItemType::Url,
            'sort' => 1,
            'is_active' => true,
        ]);

        $this->phones = MenuItem::create([
            'menu_id' => $this->menu->id,
            'parent_id' => $this->products->id,
            'title' => 'Phones',
            'link' => '/products/phones',
            'type' => MenuItemType::Url,
            'sort' => 2,
            'is_active' => true,
        ]);

        $this->services = MenuItem::create([
            'menu_id' => $this->menu->id,
            'parent_id' => $this->home->id,
            'title' => 'Services',
            'link' => '/services',
            'type' => MenuItemType::Url,
            'sort' => 2,
            'is_active' => true,
        ]);

        $this->about = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'About',
            'link' => '/about',
            'type' => MenuItemType::Url,
            'sort' => 2,
            'is_active' => true,
        ]);
    });

    test('getTree returns hierarchical structure', function () {
        $tree = MenuBuilder::getTree('tree-test-menu');

        expect($tree)->toHaveCount(2) // Home, About
            ->and($tree[0]->title)->toBe('Home')
            ->and($tree[1]->title)->toBe('About');
    });

    test('tree includes nested children', function () {
        $tree = MenuBuilder::getTree('tree-test-menu');

        $homeChildren = $tree[0]->children;

        expect($homeChildren)->toHaveCount(2)
            ->and($homeChildren[0]->title)->toBe('Products')
            ->and($homeChildren[1]->title)->toBe('Services');
    });

    test('tree includes deeply nested children', function () {
        $tree = MenuBuilder::getTree('tree-test-menu');

        $productsChildren = $tree[0]->children[0]->children;

        expect($productsChildren)->toHaveCount(2)
            ->and($productsChildren[0]->title)->toBe('Laptops')
            ->and($productsChildren[1]->title)->toBe('Phones');
    });

    test('tree children are empty array when no children', function () {
        $tree = MenuBuilder::getTree('tree-test-menu');

        $aboutChildren = $tree[1]->children;

        expect($aboutChildren)->toBeArray()
            ->and($aboutChildren)->toHaveCount(0);
    });

    test('tree respects sort order', function () {
        $tree = MenuBuilder::getTree('tree-test-menu');

        expect($tree[0]->sort)->toBe(1) // Home
            ->and($tree[1]->sort)->toBe(2); // About
    });

    test('tree children respect sort order', function () {
        $tree = MenuBuilder::getTree('tree-test-menu');

        $homeChildren = $tree[0]->children;

        expect($homeChildren[0]->sort)->toBe(1) // Products
            ->and($homeChildren[1]->sort)->toBe(2); // Services
    });
});

describe('Flat Tree Building', function () {
    beforeEach(function () {
        $this->menu = Menu::create([
            'alias' => 'flat-test-menu',
            'title' => 'Flat Test Menu',
            'is_active' => true,
        ]);

        // Create simple structure
        $this->item1 = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Item 1',
            'link' => '/item1',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        $this->item2 = MenuItem::create([
            'menu_id' => $this->menu->id,
            'parent_id' => $this->item1->id,
            'title' => 'Item 2',
            'link' => '/item1/item2',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        $this->item3 = MenuItem::create([
            'menu_id' => $this->menu->id,
            'parent_id' => $this->item2->id,
            'title' => 'Item 3',
            'link' => '/item1/item2/item3',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);
    });

    test('getFlatTree returns all items', function () {
        $flatTree = MenuBuilder::getFlatTree('flat-test-menu');

        expect($flatTree)->toHaveCount(3);
    });

    test('flat tree items have depth information', function () {
        $flatTree = MenuBuilder::getFlatTree('flat-test-menu');

        expect($flatTree[0]->depth)->toBe(0)
            ->and($flatTree[1]->depth)->toBe(1)
            ->and($flatTree[2]->depth)->toBe(2);
    });

    test('flat tree items have path information', function () {
        $flatTree = MenuBuilder::getFlatTree('flat-test-menu');

        expect($flatTree[0]->path)->toMatch('/^\d+$/')
            ->and($flatTree[1]->path)->toMatch('/^\d+\.\d+$/')
            ->and($flatTree[2]->path)->toMatch('/^\d+\.\d+\.\d+$/');
    });

    test('flat tree items have URL field', function () {
        $flatTree = MenuBuilder::getFlatTree('flat-test-menu');

        expect($flatTree[0]->url)->toBe('item1')
            ->and($flatTree[1]->url)->toBe('item1/item2')
            ->and($flatTree[2]->url)->toBe('item1/item2/item3');
    });

    test('flat tree is ordered by parent-child relationship', function () {
        $flatTree = MenuBuilder::getFlatTree('flat-test-menu');

        // Parent should come before children
        expect($flatTree[0]->title)->toBe('Item 1')
            ->and($flatTree[1]->title)->toBe('Item 2')
            ->and($flatTree[2]->title)->toBe('Item 3');
    });
});

describe('Tree Building Edge Cases', function () {
    test('returns empty array for non-existent menu', function () {
        $tree = MenuBuilder::getTree('non-existent-menu');

        expect($tree)->toBeArray()
            ->and($tree)->toHaveCount(0);
    });

    test('returns empty array for inactive menu', function () {
        $menu = Menu::create([
            'alias' => 'inactive-menu',
            'title' => 'Inactive Menu',
            'is_active' => false,
        ]);

        $tree = MenuBuilder::getTree('inactive-menu');

        expect($tree)->toBeArray()
            ->and($tree)->toHaveCount(0);
    });

    test('returns empty array for menu with no items', function () {
        Menu::create([
            'alias' => 'empty-menu',
            'title' => 'Empty Menu',
            'is_active' => true,
        ]);

        $tree = MenuBuilder::getTree('empty-menu');

        expect($tree)->toBeArray()
            ->and($tree)->toHaveCount(0);
    });

    test('filters out inactive items from tree', function () {
        $menu = Menu::create([
            'alias' => 'filter-test-menu',
            'title' => 'Filter Test Menu',
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Active',
            'link' => '/active',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Inactive',
            'link' => '/inactive',
            'type' => MenuItemType::Url,
            'is_active' => false,
        ]);

        $tree = MenuBuilder::getTree('filter-test-menu');

        expect($tree)->toHaveCount(1)
            ->and($tree[0]->title)->toBe('Active');
    });
});

describe('Complex Tree Structures', function () {
    test('handles multiple root items with children', function () {
        $menu = Menu::create([
            'alias' => 'multi-root-menu',
            'title' => 'Multi Root Menu',
            'is_active' => true,
        ]);

        // First root with children
        $root1 = MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Root 1',
            'link' => '/root1',
            'type' => MenuItemType::Url,
            'sort' => 1,
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $root1->id,
            'title' => 'Child 1-1',
            'link' => '/root1/child1',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        // Second root with children
        $root2 = MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Root 2',
            'link' => '/root2',
            'type' => MenuItemType::Url,
            'sort' => 2,
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $root2->id,
            'title' => 'Child 2-1',
            'link' => '/root2/child1',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        $tree = MenuBuilder::getTree('multi-root-menu');

        expect($tree)->toHaveCount(2)
            ->and($tree[0]->children)->toHaveCount(1)
            ->and($tree[1]->children)->toHaveCount(1);
    });

    test('handles 5+ levels of nesting', function () {
        $menu = Menu::create([
            'alias' => 'deep-menu',
            'title' => 'Deep Menu',
            'is_active' => true,
        ]);

        $level1 = MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Level 1',
            'link' => '/l1',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        $level2 = MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $level1->id,
            'title' => 'Level 2',
            'link' => '/l1/l2',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        $level3 = MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $level2->id,
            'title' => 'Level 3',
            'link' => '/l1/l2/l3',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        $level4 = MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $level3->id,
            'title' => 'Level 4',
            'link' => '/l1/l2/l3/l4',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        $level5 = MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $level4->id,
            'title' => 'Level 5',
            'link' => '/l1/l2/l3/l4/l5',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        $tree = MenuBuilder::getTree('deep-menu');

        expect($tree[0]->children[0]->children[0]->children[0]->children[0]->title)
            ->toBe('Level 5');
    });
});
