<?php

namespace Aslnbxrz\MenuBuilder\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Aslnbxrz\MenuBuilder\Models\Menu|null getMenu(string $alias)
 * @method static array getTree(string $menuAlias, ?\Illuminate\Foundation\Auth\User $user = null)
 * @method static array getFlatTree(string $menuAlias)
 * @method static void clearCache(string $menuAlias)
 *
 * @see \Aslnbxrz\MenuBuilder\MenuBuilder
 */
class MenuBuilder extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Aslnbxrz\MenuBuilder\MenuBuilder::class;
    }
}

