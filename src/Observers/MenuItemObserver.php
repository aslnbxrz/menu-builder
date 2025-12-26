<?php

namespace Aslnbxrz\MenuBuilder\Observers;

use Aslnbxrz\MenuBuilder\MenuBuilder;
use Aslnbxrz\MenuBuilder\Models\MenuItem;

class MenuItemObserver
{
    public function created(MenuItem $item): void
    {
        MenuBuilder::clearCache($item->menu->alias);
    }

    public function updated(MenuItem $item): void
    {
        MenuBuilder::clearCache($item->menu->alias);
    }

    public function deleted(MenuItem $item): void
    {
        MenuBuilder::clearCache($item->menu->alias);
    }

    public function restored(MenuItem $item): void
    {
        MenuBuilder::clearCache($item->menu->alias);
    }
}
