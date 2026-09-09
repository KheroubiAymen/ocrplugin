<?php

namespace SwissDidata\Ocr;

use Didata\Packages\installer\Package;
use Didata\Packages\installer\PackageInstaller;

class OcrPackage extends PackageInstaller
{
    public function configure(Package $package): void
    {
        $package
            ->hasMigrations()
            ->hasNoConfigFile()
            ->hasNoTranslations()
            ->hasModulePlugin([
                'name'      => 'OCR',
                'meta_data' => ['module_name' => 'ocr'],
                'icon'      => ['code' => 'document_scanner', 'color' => '#7B61FF'],
                'template'  => __DIR__.'/../resources/template.xml',
                'js'        => __DIR__.'/../resources/script.js',
            ]);
    }
}
