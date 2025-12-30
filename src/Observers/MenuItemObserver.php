<?php

namespace Aslnbxrz\MenuBuilder\Observers;

use Aslnbxrz\MenuBuilder\MenuBuilder;
use Aslnbxrz\MenuBuilder\Models\Contracts\InteractsWithMenu;
use Aslnbxrz\MenuBuilder\Models\MenuItem;
use Throwable;

class MenuItemObserver
{
    public function created(MenuItem $item): void
    {
        try {
            $item->loadMissing('menuable');
            if ($item->menuable && $item->menuable instanceof InteractsWithMenu) {
                $item->updateQuietly(['link' => sprintf('%s/%s', $item->link ?? '', $item->menuable->getMenuableIdentifier())]);
            }
        } catch (Throwable) {
        } finally {
            $item->loadMissing('menu');
            if ($item->menu) {
                MenuBuilder::clearCache($item->menu->getAttribute('alias') ?? '');
            }
        }
    }

    public function updated(MenuItem $item): void
    {
        try {
            $item->loadMissing('menuable');
            if ($item->menuable && $item->menuable instanceof InteractsWithMenu) {
                // remove old identifier if exists
                $linkParts = explode('/', $item->link ?? '');
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
            $item->loadMissing('menu');
            if ($item->menu) {
                MenuBuilder::clearCache($item->menu->getAttribute('alias') ?? '');
            }
        }
    }

    public function deleted(MenuItem $item): void
    {
        $item->loadMissing('menu');
        if ($item->menu) {
            MenuBuilder::clearCache($item->menu->getAttribute('alias') ?? '');
        }
    }

    public function restored(MenuItem $item): void
    {
        $item->loadMissing('menu');
        if ($item->menu) {
            MenuBuilder::clearCache($item->menu->getAttribute('alias') ?? '');
        }
    }
}
