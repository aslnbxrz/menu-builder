<?php

namespace Aslnbxrz\MenuBuilder\Commands;

use Illuminate\Console\Command;

class MenuBuilderCommand extends Command
{
    public $signature = 'menu-builder';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
