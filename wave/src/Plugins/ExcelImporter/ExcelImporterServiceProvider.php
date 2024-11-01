<?php

namespace Wave\Plugins\ExcelImporter;

use Wave\Plugins\PluginServiceProvider;
use Spatie\LaravelPackageTools\Package;
use Wave\Plugins\ExcelImporter\Pages\ImportExcel;

class ExcelImporterServiceProvider extends PluginServiceProvider
{
    public static string $name = 'excel-importer';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasViews();
    }

    protected function getPages(): array
    {
        return [
            ImportExcel::class,
        ];
    }
}

