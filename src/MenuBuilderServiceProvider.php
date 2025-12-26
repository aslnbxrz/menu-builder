<?php

namespace Aslnbxrz\MenuBuilder;

use Aslnbxrz\MenuBuilder\Commands\MenuBuilderCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MenuBuilderServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('menu-builder')
            ->hasConfigFile()
            ->discoversMigrations()
            ->hasCommand(MenuBuilderCommand::class);
    }
}
