# Menu Builder

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aslnbxrz/menu-builder.svg?style=flat-square)](https://packagist.org/packages/aslnbxrz/menu-builder)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/aslnbxrz/menu-builder/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/aslnbxrz/menu-builder/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/aslnbxrz/menu-builder/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/aslnbxrz/menu-builder/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/aslnbxrz/menu-builder.svg?style=flat-square)](https://packagist.org/packages/aslnbxrz/menu-builder)

Powerful and flexible menu builder package for Laravel applications. Create dynamic, hierarchical menus with support for permissions, routes, features, and more.

## Features

- 🎯 **Hierarchical Menu Structure** - Create unlimited nested menu items
- 🔐 **Permission-based Visibility** - Show/hide menu items based on user permissions
- 🛣️ **Route Validation** - Automatically validate route existence
- ⚡ **Built-in Caching** - High-performance caching for menu trees
- 🔗 **MorphTo Relationships** - Link menu items to any Eloquent model
- 🎨 **Multiple Menu Types** - Support for URL, Route, Permission, Feature, and Divider types
- 🗄️ **Database Agnostic** - Works with PostgreSQL, MySQL, and SQLite

## Installation

You can install the package via composer:

```bash
composer require aslnbxrz/menu-builder
```

The package will automatically register its service provider.

### Publish Migrations

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="menu-builder-migrations"
php artisan migrate
```

This will create two tables:
- `menus` - Stores menu definitions
- `menu_items` - Stores menu items with hierarchical structure

**Note:** By default, `title` and `description` fields are **string** type. If you need multilingual support, see the [Multilingual Support](#multilingual-support-with-spatie-translatable) section below.

### Publish Config File

You can publish the config file to customize table names and cache settings:

```bash
php artisan vendor:publish --tag="menu-builder-config"
```

This will create `config/menu-builder.php`:

```php
return [
    'menuable' => [
        'field' => 'id',
    ],

    'menu' => [
        'table' => 'menus',
    ],

    'menu_item' => [
        'table' => 'menu_items',
    ],

    'cache' => [
        'key' => 'menu:tree:',
        'ttl' => 360, // minutes
    ],
];
```

## Quick Start

1. **Install the package:**
```bash
composer require aslnbxrz/menu-builder
```

2. **Publish and run migrations:**
```bash
php artisan vendor:publish --tag="menu-builder-migrations"
php artisan migrate
```

3. **Create your first menu:**
```php
use Aslnbxrz\MenuBuilder\Models\Menu;
use Aslnbxrz\MenuBuilder\Models\MenuItem;
use Aslnbxrz\MenuBuilder\Enums\MenuItemType;

$menu = Menu::create([
    'alias' => 'main-menu',
    'title' => 'Main Menu',
    'is_active' => true,
]);

MenuItem::create([
    'menu_id' => $menu->id,
    'title' => 'Home',
    'link' => '/',
    'type' => MenuItemType::Url,
    'sort' => 1,
    'is_active' => true,
]);
```

4. **Display menu in your view:**
```php
use Aslnbxrz\MenuBuilder\Facades\MenuBuilder;

$tree = MenuBuilder::getTree('main-menu', auth()->user());
```

## Multilingual Support with Spatie Translatable

By default, the package uses **string** columns for `title` and `description` fields. If you need multilingual support, you can extend the models and use [Spatie Laravel Translatable](https://github.com/spatie/laravel-translatable) package.

#### Step 1: Install Spatie Translatable

```bash
composer require spatie/laravel-translatable
```

#### Step 2: Publish and Modify Migrations

Publish the migrations and change string to JSON:

```bash
php artisan vendor:publish --tag="menu-builder-migrations"
```

**In `create_menus_table.php`:**
```php
// Change from:
$table->string('title')->nullable();
$table->text('description')->nullable();

// To:
$table->json('title')->nullable();
$table->json('description')->nullable();
```

**In `create_menu_items_table.php`:**
```php
// Change from:
$table->string('title')->nullable();

// To:
$table->json('title')->nullable();
```

#### Step 3: Extend Models with Translatable

Create your own models that extend the package models:

**`app/Models/Menu.php`:**
```php
<?php

namespace App\Models;

use Aslnbxrz\MenuBuilder\Models\Menu as BaseMenu;
use Spatie\Translatable\HasTranslations;

class Menu extends BaseMenu
{
    use HasTranslations;

    public $translatable = ['title', 'description'];
}
```

**`app/Models/MenuItem.php`:**
```php
<?php

namespace App\Models;

use Aslnbxrz\MenuBuilder\Models\MenuItem as BaseMenuItem;
use Spatie\Translatable\HasTranslations;

class MenuItem extends BaseMenuItem
{
    use HasTranslations;

    public $translatable = ['title'];
}
```

#### Step 4: Update Service Provider

Bind your models in `app/Providers/AppServiceProvider.php`:

```php
use App\Models\Menu;
use App\Models\MenuItem;
use Aslnbxrz\MenuBuilder\Models\Menu as BaseMenu;
use Aslnbxrz\MenuBuilder\Models\MenuItem as BaseMenuItem;

public function boot(): void
{
    // Bind your extended models
    $this->app->bind(BaseMenu::class, Menu::class);
    $this->app->bind(BaseMenuItem::class, MenuItem::class);
}
```

#### Step 5: Use Translatable Models

Now you can use translations:

```php
use App\Models\Menu;
use App\Models\MenuItem;

$menu = Menu::create([
    'alias' => 'main-menu',
    'title' => ['en' => 'Main Menu', 'uz' => 'Asosiy Menyu', 'ru' => 'Главное меню'],
    'description' => ['en' => 'Main navigation menu'],
    'is_active' => true,
]);

$item = MenuItem::create([
    'menu_id' => $menu->id,
    'title' => ['en' => 'Home', 'uz' => 'Bosh sahifa', 'ru' => 'Главная'],
    'link' => '/',
    'type' => MenuItemType::Url,
]);

// Get translation
$item->getTranslation('title', 'uz'); // 'Bosh sahifa'
$item->title; // Returns translation for current locale
```

### Extending Models for Custom Functionality

You can extend the package models to add custom functionality, relationships, or methods:

**Example: Adding custom methods**

```php
<?php

namespace App\Models;

use Aslnbxrz\MenuBuilder\Models\MenuItem as BaseMenuItem;

class MenuItem extends BaseMenuItem
{
    public function getFullUrlAttribute(): string
    {
        return url($this->link);
    }

    public function isActiveRoute(): bool
    {
        return request()->is($this->link);
    }
}
```

**Example: Adding relationships**

```php
<?php

namespace App\Models;

use Aslnbxrz\MenuBuilder\Models\Menu as BaseMenu;
use App\Models\User;

class Menu extends BaseMenu
{
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

## Usage

### Basic Usage

#### Creating a Menu

**Default (String fields):**

```php
use Aslnbxrz\MenuBuilder\Models\Menu;

$menu = Menu::create([
    'alias' => 'main-menu',
    'title' => 'Main Menu',
    'description' => 'Main navigation menu',
    'is_active' => true,
]);
```

**With Spatie Translatable (if you extended models):**

```php
use App\Models\Menu; // Your extended model

$menu = Menu::create([
    'alias' => 'main-menu',
    'title' => ['en' => 'Main Menu', 'uz' => 'Asosiy Menyu', 'ru' => 'Главное меню'],
    'description' => ['en' => 'Main navigation menu', 'uz' => 'Asosiy navigatsiya menyusi'],
    'is_active' => true,
]);
```

#### Creating Menu Items

```php
use Aslnbxrz\MenuBuilder\Models\MenuItem;
use Aslnbxrz\MenuBuilder\Enums\MenuItemType;

// Simple URL menu item
// Default: title => 'Home'
// With Translatable: title => ['en' => 'Home', 'uz' => 'Bosh sahifa']
$homeItem = MenuItem::create([
    'menu_id' => $menu->id,
    'title' => 'Home', // or ['en' => 'Home', 'uz' => 'Bosh sahifa'] if using Spatie Translatable
    'link' => '/',
    'type' => MenuItemType::Url,
    'sort' => 1,
    'is_active' => true,
]);

// Route-based menu item
$aboutItem = MenuItem::create([
    'menu_id' => $menu->id,
    'title' => 'About', // or ['en' => 'About', 'uz' => 'Haqida'] if using Spatie Translatable
    'type' => MenuItemType::Route,
    'meta' => ['route' => 'about'],
    'sort' => 2,
    'is_active' => true,
]);

// Permission-based menu item (only visible if user has permission)
$adminItem = MenuItem::create([
    'menu_id' => $menu->id,
    'title' => 'Admin Panel', // or ['en' => 'Admin Panel', 'uz' => 'Admin Panel'] if using Spatie Translatable
    'link' => '/admin',
    'type' => MenuItemType::Permission,
    'meta' => ['permission' => 'access-admin'],
    'sort' => 3,
    'is_active' => true,
]);

// Nested menu item (child)
$childItem = MenuItem::create([
    'menu_id' => $menu->id,
    'parent_id' => $homeItem->id,
    'title' => 'Sub Page', // or ['en' => 'Sub Page', 'uz' => 'Pastki sahifa'] if using Spatie Translatable
    'link' => '/sub-page',
    'type' => MenuItemType::Url,
    'sort' => 1,
    'is_active' => true,
]);

// Divider
$divider = MenuItem::create([
    'menu_id' => $menu->id,
    'type' => MenuItemType::Divider,
    'sort' => 4,
    'is_active' => true,
]);
```

### Retrieving Menus

#### Using Facade

```php
use Aslnbxrz\MenuBuilder\Facades\MenuBuilder;

// Get menu by alias
$menu = MenuBuilder::getMenu('main-menu');

// Get tree structure (ready for frontend)
$tree = MenuBuilder::getTree('main-menu', auth()->user());

// Get flat tree structure
$flatTree = MenuBuilder::getTree('main-menu');
```

#### Using Dependency Injection

```php
use Aslnbxrz\MenuBuilder\MenuBuilder;

class MenuController extends Controller
{
    public function __construct(
        protected MenuBuilder $menuBuilder
    ) {}

    public function index()
    {
        $tree = $this->menuBuilder->getTree('main-menu', auth()->user());
        
        return view('menu', compact('tree'));
    }
}
```

### Menu Item Types

The package supports several menu item types:

#### 1. URL (`MenuItemType::Url`)
Simple URL link that's always visible.

```php
MenuItem::create([
    'menu_id' => $menu->id,
    'title' => ['en' => 'Home'],
    'link' => '/',
    'type' => MenuItemType::Url,
]);
```

#### 2. Route (`MenuItemType::Route`)
Route-based link that validates route existence.

```php
MenuItem::create([
    'menu_id' => $menu->id,
    'title' => ['en' => 'About'],
    'type' => MenuItemType::Route,
    'meta' => ['route' => 'about'],
]);
```

#### 3. Permission (`MenuItemType::Permission`)
Only visible if user has the specified permission.

```php
MenuItem::create([
    'menu_id' => $menu->id,
    'title' => ['en' => 'Admin'],
    'link' => '/admin',
    'type' => MenuItemType::Permission,
    'meta' => ['permission' => 'access-admin'],
]);
```

#### 4. Feature (`MenuItemType::Feature`)
Only visible if feature is active (requires feature flag package).

```php
MenuItem::create([
    'menu_id' => $menu->id,
    'title' => ['en' => 'Beta Feature'],
    'link' => '/beta',
    'type' => MenuItemType::Feature,
    'meta' => ['feature' => 'beta-feature'],
]);
```

#### 5. Divider (`MenuItemType::Divider`)
Visual separator in menu.

```php
MenuItem::create([
    'menu_id' => $menu->id,
    'type' => MenuItemType::Divider,
]);
```

### Linking Menu Items to Models (Menuable)

You can link menu items to any Eloquent model using polymorphic relationships:

#### 1. Make Your Model Menuable

First, implement the `InteractsWithMenu` interface and use the `CanBeMenu` trait:

```php
use Aslnbxrz\MenuBuilder\Models\Contracts\InteractsWithMenu;
use Aslnbxrz\MenuBuilder\Models\Concerns\CanBeMenu;
use Illuminate\Database\Eloquent\Model;

class Post extends Model implements InteractsWithMenu
{
    use CanBeMenu;

    protected static string $menuable_field = 'slug'; // or 'id', 'title', etc.
}
```

#### 2. Create Menu Item with Model Link

```php
$post = Post::find(1);

MenuItem::create([
    'menu_id' => $menu->id,
    'title' => ['en' => $post->title],
    'link' => '/posts',
    'type' => MenuItemType::Url,
    'menuable_type' => Post::class,
    'menuable_id' => $post->id,
    'menuable_value' => $post->slug, // Will be auto-updated
]);
```

The `link` field will be automatically updated to include the model identifier when the menu item is created or updated.

### Cache Management

Menus are automatically cached for better performance. Cache is cleared automatically when menus or menu items are created, updated, or deleted.

#### Manual Cache Clearing

```php
use Aslnbxrz\MenuBuilder\Facades\MenuBuilder;

// Clear cache for specific menu
MenuBuilder::clearCache('main-menu');
```

### Query Scopes

The package provides useful query scopes:

#### Menu Scopes

```php
use Aslnbxrz\MenuBuilder\Models\Menu;

// Get active menus
$activeMenus = Menu::active()->get();

// Get menu by alias
$menu = Menu::alias('main-menu')->first();
```

#### MenuItem Scopes

```php
use Aslnbxrz\MenuBuilder\Models\MenuItem;

// Get root items (no parent)
$rootItems = MenuItem::root()->get();

// Get active items
$activeItems = MenuItem::active()->get();

// Get ordered items
$orderedItems = MenuItem::ordered()->get();

// Get items for specific menu
$items = MenuItem::forMenu('main-menu')->get();
```

## API Integration

Since this package is primarily designed for frontend API integration, here are complete examples for creating API endpoints and consuming them in various frontend frameworks.

### Creating API Endpoints

Create API routes and controllers to serve menu data to your frontend:

**`routes/api.php`:**
```php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MenuController;

Route::prefix('menus')->group(function () {
    Route::get('/{alias}', [MenuController::class, 'show']);
    Route::get('/{alias}/tree', [MenuController::class, 'tree']);
});
```

**`app/Http/Controllers/Api/MenuController.php`:**
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Aslnbxrz\MenuBuilder\Facades\MenuBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Get menu by alias
     */
    public function show(string $alias): JsonResponse
    {
        $menu = MenuBuilder::getMenu($alias);
        
        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $menu->id,
                'alias' => $menu->alias,
                'title' => $menu->title,
                'description' => $menu->description,
                'is_active' => $menu->is_active,
            ],
        ]);
    }
    
    /**
     * Get menu tree structure
     */
    public function tree(Request $request, string $alias): JsonResponse
    {
        $user = $request->user(); // Get authenticated user
        
        $tree = MenuBuilder::getTree($alias, $user);
        
        return response()->json([
            'success' => true,
            'data' => $tree,
        ]);
    }
}
```

### API Response Example

**GET `/api/menus/main-menu/tree`**

Response:
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "menu_id": 1,
            "parent_id": null,
            "title": "Home",
            "link": "/",
            "type": "url",
            "url": "/",
            "is_active": true,
            "sort": 1,
            "meta": null,
            "depth": 0,
            "path": "1",
            "children": [
                {
                    "id": 2,
                    "menu_id": 1,
                    "parent_id": 1,
                    "title": "Sub Page",
                    "link": "/sub-page",
                    "type": "url",
                    "url": "/sub-page",
                    "is_active": true,
                    "sort": 1,
                    "meta": null,
                    "depth": 1,
                    "path": "1.2",
                    "children": []
                }
            ]
        },
        {
            "id": 3,
            "menu_id": 1,
            "parent_id": null,
            "title": "About",
            "link": null,
            "type": "route",
            "url": "/about",
            "is_active": true,
            "sort": 2,
            "meta": {
                "route": "about"
            },
            "depth": 0,
            "path": "3",
            "children": []
        }
    ]
}
```

### Frontend Integration Examples

#### React Example

```jsx
import { useEffect, useState } from 'react';
import axios from 'axios';

function MenuComponent({ menuAlias }) {
    const [menuTree, setMenuTree] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchMenu = async () => {
            try {
                const response = await axios.get(`/api/menus/${menuAlias}/tree`, {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    }
                });
                
                if (response.data.success) {
                    setMenuTree(response.data.data);
                }
            } catch (error) {
                console.error('Error fetching menu:', error);
            } finally {
                setLoading(false);
            }
        };

        fetchMenu();
    }, [menuAlias]);

    const renderMenu = (items) => {
        return (
            <ul>
                {items.map((item) => (
                    <li key={item.id}>
                        {item.type === 'divider' ? (
                            <hr />
                        ) : (
                            <>
                                <a href={item.url || item.link || '#'}>
                                    {item.title}
                                </a>
                                {item.children && item.children.length > 0 && (
                                    <ul>{renderMenu(item.children)}</ul>
                                )}
                            </>
                        )}
                    </li>
                ))}
            </ul>
        );
    };

    if (loading) return <div>Loading menu...</div>;

    return <nav>{renderMenu(menuTree)}</nav>;
}

export default MenuComponent;
```

#### Vue.js Example

```vue
<template>
    <nav v-if="!loading">
        <ul>
            <menu-item
                v-for="item in menuTree"
                :key="item.id"
                :item="item"
            />
        </ul>
    </nav>
    <div v-else>Loading menu...</div>
</template>

<script>
import axios from 'axios';
import MenuItem from './MenuItem.vue';

export default {
    components: {
        MenuItem,
    },
    props: {
        menuAlias: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            menuTree: [],
            loading: true,
        };
    },
    async mounted() {
        try {
            const response = await axios.get(`/api/menus/${this.menuAlias}/tree`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });
            
            if (response.data.success) {
                this.menuTree = response.data.data;
            }
        } catch (error) {
            console.error('Error fetching menu:', error);
        } finally {
            this.loading = false;
        }
    },
};
</script>
```

**`MenuItem.vue` component:**
```vue
<template>
    <li>
        <div v-if="item.type === 'divider'" class="divider"></div>
        <template v-else>
            <a :href="item.url || item.link || '#'">
                {{ item.title }}
            </a>
            <ul v-if="item.children && item.children.length > 0">
                <menu-item
                    v-for="child in item.children"
                    :key="child.id"
                    :item="child"
                />
            </ul>
        </template>
    </li>
</template>

<script>
export default {
    name: 'MenuItem',
    props: {
        item: {
            type: Object,
            required: true,
        },
    },
};
</script>
```

#### Next.js Example

```tsx
// app/api/menus/[alias]/route.ts
import { NextRequest, NextResponse } from 'next/server';

export async function GET(
    request: NextRequest,
    { params }: { params: { alias: string } }
) {
    try {
        const token = request.headers.get('authorization');
        const response = await fetch(
            `${process.env.API_URL}/api/menus/${params.alias}/tree`,
            {
                headers: {
                    'Authorization': token || '',
                },
            }
        );
        
        const data = await response.json();
        return NextResponse.json(data);
    } catch (error) {
        return NextResponse.json(
            { success: false, message: 'Error fetching menu' },
            { status: 500 }
        );
    }
}
```

```tsx
// components/Menu.tsx
'use client';

import { useEffect, useState } from 'react';

interface MenuItem {
    id: number;
    title: string;
    link: string | null;
    url: string;
    type: string;
    children: MenuItem[];
}

export default function Menu({ alias }: { alias: string }) {
    const [menuTree, setMenuTree] = useState<MenuItem[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetch(`/api/menus/${alias}`)
            .then((res) => res.json())
            .then((data) => {
                if (data.success) {
                    setMenuTree(data.data);
                }
            })
            .catch(console.error)
            .finally(() => setLoading(false));
    }, [alias]);

    if (loading) return <div>Loading...</div>;

    return (
        <nav>
            <MenuItems items={menuTree} />
        </nav>
    );
}

function MenuItems({ items }: { items: MenuItem[] }) {
    return (
        <ul>
            {items.map((item) => (
                <li key={item.id}>
                    {item.type === 'divider' ? (
                        <hr />
                    ) : (
                        <>
                            <a href={item.url || item.link || '#'}>
                                {item.title}
                            </a>
                            {item.children.length > 0 && (
                                <MenuItems items={item.children} />
                            )}
                        </>
                    )}
                </li>
            ))}
        </ul>
    );
}
```

#### Blade Template (Server-side Rendering)

```blade
@php
    $menuTree = \Aslnbxrz\MenuBuilder\Facades\MenuBuilder::getTree('main-menu', auth()->user());
@endphp

<nav>
    <ul>
        @foreach($menuTree as $item)
            @include('menu.item', ['item' => $item])
        @endforeach
    </ul>
</nav>
```

#### Menu Item Partial (`resources/views/menu/item.blade.php`)

**Default (String fields):**

```blade
@if($item->type === 'divider')
    <li class="divider"></li>
@else
    <li>
        <a href="{{ $item->url ?? $item->link ?? '#' }}">
            {{ $item->title }}
        </a>
        
        @if(count($item->children ?? []) > 0)
            <ul>
                @foreach($item->children as $child)
                    @include('menu.item', ['item' => $child])
                @endforeach
            </ul>
        @endif
    </li>
@endif
```

**With Spatie Translatable (if you extended models):**

```blade
@if($item->type === 'divider')
    <li class="divider"></li>
@else
    <li>
        <a href="{{ $item->url ?? $item->link ?? '#' }}">
            {{ $item->title }} {{-- Automatically returns translation for current locale --}}
        </a>
        
        @if(count($item->children ?? []) > 0)
            <ul>
                @foreach($item->children as $child)
                    @include('menu.item', ['item' => $child])
                @endforeach
            </ul>
        @endif
    </li>
@endif
```

Or get specific translation:

```blade
{{ $item->getTranslation('title', 'uz') }}
```


## API Reference

### MenuBuilder Class

#### `getMenu(string $alias): ?Menu`
Get a menu by its alias.

```php
$menu = MenuBuilder::getMenu('main-menu');
```

#### `getTree(string $menuAlias, ?User $user = null): array`
Get a hierarchical tree structure of menu items, filtered by user permissions.

```php
$tree = MenuBuilder::getTree('main-menu', auth()->user());
```

Returns an array of menu items with nested `children` arrays.

#### `getFlatTree(string $menuAlias): array`
Get a flat array of all menu items with depth information.

```php
$flatTree = MenuBuilder::getFlatTree('main-menu');
```

#### `clearCache(string $menuAlias): void`
Clear cache for a specific menu.

```php
MenuBuilder::clearCache('main-menu');
```

### Menu Model

#### Attributes
- `id` - Menu ID
- `title` - JSON field for multilingual titles
- `description` - JSON field for multilingual descriptions
- `alias` - Unique menu identifier
- `is_active` - Active status
- `meta` - Additional metadata (JSON)
- `created_at` - Creation timestamp
- `updated_at` - Update timestamp

#### Relationships
- `items()` - HasMany relationship to MenuItem

#### Scopes
- `active()` - Filter active menus
- `alias(string $alias)` - Filter by alias

### MenuItem Model

#### Attributes
- `id` - Menu item ID
- `menu_id` - Parent menu ID
- `parent_id` - Parent menu item ID (for nesting)
- `menuable_type` - Polymorphic relationship type
- `menuable_id` - Polymorphic relationship ID
- `menuable_value` - Value from linked model
- `title` - JSON field for multilingual titles
- `link` - URL or route
- `type` - MenuItemType enum
- `is_active` - Active status
- `sort` - Sort order
- `meta` - Additional metadata (JSON)
- `created_at` - Creation timestamp
- `updated_at` - Update timestamp

#### Relationships
- `menu()` - BelongsTo Menu
- `parent()` - BelongsTo MenuItem (self)
- `children()` - HasMany MenuItem (self)
- `menuable()` - MorphTo relationship

#### Scopes
- `root()` - Filter root items (no parent)
- `active()` - Filter active items
- `ordered()` - Order by sort field
- `forMenu(string $alias)` - Filter by menu alias

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Bexruz Aslonov](https://github.com/aslnbxrz)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
