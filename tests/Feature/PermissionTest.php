<?php

use Aslnbxrz\MenuBuilder\Enums\MenuItemType;
use Aslnbxrz\MenuBuilder\Facades\MenuBuilder;
use Aslnbxrz\MenuBuilder\Models\Menu;
use Aslnbxrz\MenuBuilder\Models\MenuItem;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;

// Create a mock user class for testing
class TestUser extends User
{
    protected $fillable = ['name', 'email'];

    protected array $permissions = [];

    public function setPermissions(array $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function can($abilities, $arguments = []): bool
    {
        if (is_string($abilities)) {
            return in_array($abilities, $this->permissions);
        }

        return false;
    }
}

describe('Permission-Based Filtering', function () {
    beforeEach(function () {
        $this->menu = Menu::create([
            'alias' => 'permission-menu',
            'title' => 'Permission Menu',
            'is_active' => true,
        ]);

        // Public item (URL type - always visible)
        $this->publicItem = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Public',
            'link' => '/public',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        // Admin item (requires permission)
        $this->adminItem = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Admin',
            'link' => '/admin',
            'type' => MenuItemType::Permission,
            'meta' => ['permission' => 'access-admin'],
            'is_active' => true,
        ]);

        // Editor item (requires permission)
        $this->editorItem = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Editor',
            'link' => '/editor',
            'type' => MenuItemType::Permission,
            'meta' => ['permission' => 'access-editor'],
            'is_active' => true,
        ]);
    });

    test('guest user sees only public items', function () {
        $tree = MenuBuilder::getTree('permission-menu', null);

        expect($tree)->toHaveCount(1)
            ->and($tree[0]->title)->toBe('Public');
    });

    test('user with admin permission sees admin items', function () {
        $user = new TestUser();
        $user->setPermissions(['access-admin']);

        $tree = MenuBuilder::getTree('permission-menu', $user);

        $titles = collect($tree)->pluck('title')->toArray();

        expect($tree)->toHaveCount(2)
            ->and($titles)->toContain('Public')
            ->and($titles)->toContain('Admin')
            ->and($titles)->not->toContain('Editor');
    });

    test('user with multiple permissions sees all accessible items', function () {
        $user = new TestUser();
        $user->setPermissions(['access-admin', 'access-editor']);

        $tree = MenuBuilder::getTree('permission-menu', $user);

        expect($tree)->toHaveCount(3);
    });

    test('user without permissions sees only public items', function () {
        $user = new TestUser();
        $user->setPermissions([]);

        $tree = MenuBuilder::getTree('permission-menu', $user);

        expect($tree)->toHaveCount(1)
            ->and($tree[0]->title)->toBe('Public');
    });
});

describe('Nested Permission Filtering', function () {
    beforeEach(function () {
        $this->menu = Menu::create([
            'alias' => 'nested-permission-menu',
            'title' => 'Nested Permission Menu',
            'is_active' => true,
        ]);

        // Public parent
        $this->publicParent = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Public Parent',
            'link' => '/public',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        // Admin child under public parent
        $this->adminChild = MenuItem::create([
            'menu_id' => $this->menu->id,
            'parent_id' => $this->publicParent->id,
            'title' => 'Admin Child',
            'link' => '/public/admin',
            'type' => MenuItemType::Permission,
            'meta' => ['permission' => 'access-admin'],
            'is_active' => true,
        ]);

        // Public child under public parent
        $this->publicChild = MenuItem::create([
            'menu_id' => $this->menu->id,
            'parent_id' => $this->publicParent->id,
            'title' => 'Public Child',
            'link' => '/public/child',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);
    });

    test('parent visible if has accessible children', function () {
        $user = new TestUser();
        $user->setPermissions([]);

        $tree = MenuBuilder::getTree('nested-permission-menu', $user);

        // Parent should be visible because it has a public child
        expect($tree)->toHaveCount(1)
            ->and($tree[0]->title)->toBe('Public Parent')
            ->and($tree[0]->children)->toHaveCount(1)
            ->and($tree[0]->children[0]->title)->toBe('Public Child');
    });

    test('user with permission sees both children', function () {
        $user = new TestUser();
        $user->setPermissions(['access-admin']);

        $tree = MenuBuilder::getTree('nested-permission-menu', $user);

        expect($tree[0]->children)->toHaveCount(2);
    });
});

describe('Route-Based Filtering', function () {
    test('route items visible only if route exists', function () {
        // Register a route
        Route::get('/test-route', fn() => 'test')->name('test.route');

        $menu = Menu::create([
            'alias' => 'route-menu',
            'title' => 'Route Menu',
            'is_active' => true,
        ]);

        // Valid route
        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Valid Route',
            'type' => MenuItemType::Route,
            'meta' => ['route' => 'test.route'],
            'is_active' => true,
        ]);

        // Invalid route
        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Invalid Route',
            'type' => MenuItemType::Route,
            'meta' => ['route' => 'non.existent.route'],
            'is_active' => true,
        ]);

        // Ensure routes are refreshed
        Route::getRoutes()->refreshNameLookups();

        $tree = MenuBuilder::getTree('route-menu');

        expect($tree)->toHaveCount(1)
            ->and($tree[0]->title)->toBe('Valid Route');
    });
});

describe('Divider Type', function () {
    test('dividers are always visible', function () {
        $menu = Menu::create([
            'alias' => 'divider-menu',
            'title' => 'Divider Menu',
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Item 1',
            'link' => '/item1',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'type' => MenuItemType::Divider,
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Item 2',
            'link' => '/item2',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        $tree = MenuBuilder::getTree('divider-menu');

        expect($tree)->toHaveCount(3)
            ->and($tree[1]->type)->toBe(MenuItemType::Divider->value);
    });
});

describe('Complex Permission Scenarios', function () {
    test('deeply nested permission filtering', function () {
        $menu = Menu::create([
            'alias' => 'deep-permission-menu',
            'title' => 'Deep Permission Menu',
            'is_active' => true,
        ]);

        // Level 1: Public
        $level1 = MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'L1 Public',
            'link' => '/l1',
            'type' => MenuItemType::Url,
            'is_active' => true,
        ]);

        // Level 2: Admin required
        $level2 = MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $level1->id,
            'title' => 'L2 Admin',
            'link' => '/l1/l2',
            'type' => MenuItemType::Permission,
            'meta' => ['permission' => 'access-admin'],
            'is_active' => true,
        ]);

        // Level 3: Admin required (child of admin item)
        // If this was public, the parent would be shown. So we make it restricted too.
        MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $level2->id,
            'title' => 'L3 Admin',
            'link' => '/l1/l2/l3',
            'type' => MenuItemType::Permission,
            'meta' => ['permission' => 'access-admin'],
            'is_active' => true,
        ]);

        // Guest user
        $guestTree = MenuBuilder::getTree('deep-permission-menu', null);
        expect($guestTree[0]->children)->toHaveCount(0);

        // Admin user
        $adminUser = new TestUser();
        $adminUser->setPermissions(['access-admin']);
        $adminTree = MenuBuilder::getTree('deep-permission-menu', $adminUser);
        expect($adminTree[0]->children)->toHaveCount(1)
            ->and($adminTree[0]->children[0]->children)->toHaveCount(1);
    });
});
