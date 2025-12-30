<?php

namespace Aslnbxrz\MenuBuilder\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphTo;

trait CanBeMenu
{
    protected static string $menuable_field;

    public function menuable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getMenuableIdentifier(): mixed
    {
        return $this->{static::getMenuableField()};
    }

    public static function getMenuableField(): string
    {
        return static::$menuable_field;
    }
}
