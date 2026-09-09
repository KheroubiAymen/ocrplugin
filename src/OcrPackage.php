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
            ->hasNoTranslations();
    }
}
