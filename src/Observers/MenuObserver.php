<?php

namespace Aslnbxrz\MenuBuilder\Observers;

use Aslnbxrz\MenuBuilder\MenuBuilder;
use Aslnbxrz\MenuBuilder\Models\Menu;

class MenuObserver
{
    public function created(Menu $menu): void
    {
        MenuBuilder::clearCache($menu->alias);
    }

    public function updated(Menu $menu): void
    {
        MenuBuilder::clearCache($menu->alias);
    }

    public function deleted(Menu $menu): void
    {
        MenuBuilder::clearCache($menu->alias);
    }

    public function restored(Menu $menu): void
    {
        MenuBuilder::clearCache($menu->alias);
    }
}
