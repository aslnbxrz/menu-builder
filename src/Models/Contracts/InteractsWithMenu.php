<?php

namespace Aslnbxrz\MenuBuilder\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphTo;

interface InteractsWithMenu
{
    public function menuable(): MorphTo;

    public function getMenuableIdentifier(): mixed;
}
