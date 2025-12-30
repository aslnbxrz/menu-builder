<?php

use Aslnbxrz\MenuBuilder\Enums\MenuItemType;
use Aslnbxrz\MenuBuilder\Facades\MenuBuilder;
use Aslnbxrz\MenuBuilder\Models\Menu;
use Aslnbxrz\MenuBuilder\Models\MenuItem;

beforeEach(function () {
    // Create a test menu structure
    // Home > Products > Laptops > MacBook
    //      > Services > Design
    //      > About

    $this->menu = Menu::create([
        'alias' => 'test-breadcrumb-menu',
        'title' => 'Test Breadcrumb Menu',
        'is_active' => true,
    ]);

    // Root items
    $this->home = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'Home',
        'link' => '/',
        'type' => MenuItemType::Url,
        'sort' => 1,
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

    // Home > Products
    $this->products = MenuItem::create([
        'menu_id' => $this->menu->id,
        'parent_id' => $this->home->id,
        'title' => 'Products',
        'link' => '/products',
        'type' => MenuItemType::Url,
        'sort' => 1,
        'is_active' => true,
    ]);

    // Home > Services
    $this->services = MenuItem::create([
        'menu_id' => $this->menu->id,
        'parent_id' => $this->home->id,
        'title' => 'Services',
        'link' => '/services',
        'type' => MenuItemType::Url,
        'sort' => 2,
        'is_active' => true,
    ]);

    // Home > Products > Laptops
    $this->laptops = MenuItem::create([
        'menu_id' => $this->menu->id,
        'parent_id' => $this->products->id,
        'title' => 'Laptops',
        'link' => '/products/laptops',
        'type' => MenuItemType::Url,
        'sort' => 1,
        'is_active' => true,
    ]);

    // Home > Products > Laptops > MacBook
    $this->macbook = MenuItem::create([
        'menu_id' => $this->menu->id,
        'parent_id' => $this->laptops->id,
        'title' => 'MacBook',
        'link' => '/products/laptops/macbook',
        'type' => MenuItemType::Url,
        'sort' => 1,
        'is_active' => true,
    ]);

    // Home > Services > Design
    $this->design = MenuItem::create([
        'menu_id' => $this->menu->id,
        'parent_id' => $this->services->id,
        'title' => 'Design',
        'link' => '/services/design',
        'type' => MenuItemType::Url,
        'sort' => 1,
        'is_active' => true,
    ]);
});

test('can get breadcrumbs for root item', function () {
    $breadcrumbs = MenuBuilder::getBreadcrumbs('test-breadcrumb-menu', '/');

    expect($breadcrumbs)->toHaveCount(1)
        ->and($breadcrumbs[0]['title'])->toBe('Home')
        ->and($breadcrumbs[0]['depth'])->toBe(0);
});

test('can get breadcrumbs for nested item', function () {
    $breadcrumbs = MenuBuilder::getBreadcrumbs('test-breadcrumb-menu', '/products/laptops/macbook');

    expect($breadcrumbs)->toHaveCount(4)
        ->and($breadcrumbs[0]['title'])->toBe('Home')
        ->and($breadcrumbs[1]['title'])->toBe('Products')
        ->and($breadcrumbs[2]['title'])->toBe('Laptops')
        ->and($breadcrumbs[3]['title'])->toBe('MacBook');
});

test('breadcrumbs are ordered by depth', function () {
    $breadcrumbs = MenuBuilder::getBreadcrumbs('test-breadcrumb-menu', '/products/laptops/macbook');

    expect($breadcrumbs[0]['depth'])->toBe(0)
        ->and($breadcrumbs[1]['depth'])->toBe(1)
        ->and($breadcrumbs[2]['depth'])->toBe(2)
        ->and($breadcrumbs[3]['depth'])->toBe(3);
});

test('can get breadcrumbs for second level item', function () {
    $breadcrumbs = MenuBuilder::getBreadcrumbs('test-breadcrumb-menu', '/products');

    expect($breadcrumbs)->toHaveCount(2)
        ->and($breadcrumbs[0]['title'])->toBe('Home')
        ->and($breadcrumbs[1]['title'])->toBe('Products');
});

test('returns empty array for non-existent url', function () {
    $breadcrumbs = MenuBuilder::getBreadcrumbs('test-breadcrumb-menu', '/non-existent');

    expect($breadcrumbs)->toBeEmpty();
});

test('returns empty array for non-existent menu', function () {
    $breadcrumbs = MenuBuilder::getBreadcrumbs('non-existent-menu', '/');

    expect($breadcrumbs)->toBeEmpty();
});

test('can include home when url not found', function () {
    $breadcrumbs = MenuBuilder::getBreadcrumbs('test-breadcrumb-menu', '/non-existent', includeHome: true);

    expect($breadcrumbs)->toHaveCount(1)
        ->and($breadcrumbs[0]['title'])->toBe('Home');
});

test('breadcrumb item contains required fields', function () {
    $breadcrumbs = MenuBuilder::getBreadcrumbs('test-breadcrumb-menu', '/products');

    expect($breadcrumbs[0])->toHaveKeys(['id', 'title', 'url', 'link', 'type', 'depth', 'meta']);
});

test('can get breadcrumbs with trailing slash', function () {
    $breadcrumbs = MenuBuilder::getBreadcrumbs('test-breadcrumb-menu', '/products/');

    expect($breadcrumbs)->toHaveCount(2)
        ->and($breadcrumbs[1]['title'])->toBe('Products');
});

test('can get breadcrumbs without leading slash', function () {
    $breadcrumbs = MenuBuilder::getBreadcrumbs('test-breadcrumb-menu', 'products');

    expect($breadcrumbs)->toHaveCount(2)
        ->and($breadcrumbs[1]['title'])->toBe('Products');
});

test('getBreadcrumbsByRoute works with route name', function () {
    // Create a route-based menu item
    Route::get('/test-route', function () {
        return 'test';
    })->name('test.route');

    $routeItem = MenuItem::create([
        'menu_id' => $this->menu->id,
        'parent_id' => $this->home->id,
        'title' => 'Test Route',
        'type' => MenuItemType::Route,
        'meta' => ['route' => 'test.route'],
        'sort' => 3,
        'is_active' => true,
    ]);

    $breadcrumbs = MenuBuilder::getBreadcrumbsByRoute('test-breadcrumb-menu', 'test.route');

    expect($breadcrumbs)->toHaveCount(2)
        ->and($breadcrumbs[0]['title'])->toBe('Home')
        ->and($breadcrumbs[1]['title'])->toBe('Test Route');
});

test('getBreadcrumbsByRoute returns empty for non-existent route', function () {
    $breadcrumbs = MenuBuilder::getBreadcrumbsByRoute('test-breadcrumb-menu', 'non.existent.route');

    expect($breadcrumbs)->toBeEmpty();
});

test('getBreadcrumbsByRoute can include home when route not found', function () {
    $breadcrumbs = MenuBuilder::getBreadcrumbsByRoute('test-breadcrumb-menu', 'non.existent.route', includeHome: true);

    expect($breadcrumbs)->toHaveCount(1)
        ->and($breadcrumbs[0]['title'])->toBe('Home');
});

test('breadcrumbs work with different menu structures', function () {
    $breadcrumbs1 = MenuBuilder::getBreadcrumbs('test-breadcrumb-menu', '/services/design');
    $breadcrumbs2 = MenuBuilder::getBreadcrumbs('test-breadcrumb-menu', '/about');

    expect($breadcrumbs1)->toHaveCount(3)
        ->and($breadcrumbs1[0]['title'])->toBe('Home')
        ->and($breadcrumbs1[1]['title'])->toBe('Services')
        ->and($breadcrumbs1[2]['title'])->toBe('Design')
        ->and($breadcrumbs2)->toHaveCount(1)
        ->and($breadcrumbs2[0]['title'])->toBe('About');
});
