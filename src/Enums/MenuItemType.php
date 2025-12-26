<?php

namespace Aslnbxrz\MenuBuilder\Enums;

enum MenuItemType: string
{
    case Url = 'url';
    case Route = 'route';
    case Permission = 'permission';
    case Feature = 'feature';
    case Divider = 'divider';
}
