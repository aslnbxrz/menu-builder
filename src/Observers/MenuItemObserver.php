<?php

namespace Aslnbxrz\MenuBuilder\Observers;

use Aslnbxrz\MenuBuilder\MenuBuilder;
use Aslnbxrz\MenuBuilder\Models\MenuItem;
use Throwable;

class MenuItemObserver
{
    public function created(MenuItem $item): void
    {
        try {
            if ($item->menuable) {
                $item->updateQuietly(['link' => sprintf('%s/%s', $item->link, $item->menuable->getMenuableIdentifier())]);
            }
        } catch (Throwable) {
        } finally {
            MenuBuilder::clearCache($item->menu->alias);
        }
    }

    public function updated(MenuItem $item): void
    {
        try {
            if ($item->menuable) {
                // remove old identifier if exists
                $linkParts = explode('/', $item->link);
                if (end($linkParts) !== $item->menuable->getMenuableIdentifier()) {
                    array_pop($linkParts);
                    $baseLink = implode('/', $linkParts);
                } else {
                    $baseLink = implode('/', array_slice($linkParts, 0, -1));
                }
                $item->updateQuietly(['link' => sprintf('%s/%s', $baseLink, $item->menuable->getMenuableIdentifier())]);
            }
        } catch (Throwable) {
        } finally {
            MenuBuilder::clearCache($item->menu->alias);
        }
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
