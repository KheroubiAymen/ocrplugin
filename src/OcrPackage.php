<?php

namespace SwissDidata\Ocr;

use App\Models\Plugin;
use App\Models\UserRoute;
use Didata\Packages\installer\Package;
use Didata\Packages\installer\PackageInstaller;

class OcrPackage extends PackageInstaller
{
    private const PLUGIN_NAME = 'OCR';

    private const ROUTE_NAME = 'ocr_extract_text';

    public function configure(Package $package): void
    {
        $package
            ->hasMigrations()
            ->hasNoConfigFile()
            ->hasNoTranslations()
            ->hasModulePlugin([
                'name'      => self::PLUGIN_NAME,
                'meta_data' => ['module_name' => 'ocr'],
                'icon'      => ['code' => 'document_scanner', 'color' => '#7B61FF'],
                'template'  => __DIR__.'/../resources/template.xml',
                'js'        => __DIR__.'/../resources/script.js',
            ]);
    }

    public function boot()
    {
        parent::boot();

        // The marketplace install flow runs `migrate` (where our route is seeded)
        // BEFORE `marketplace:sync-contributions` (which creates the Plugin row
        // from hasModulePlugin() above) — so the route can't know the plugin's id
        // at migration time. Nothing in DiData's contribution sync links a
        // package's routes to its plugin either (that only happens through the
        // admin UI). So we link them ourselves here, on every boot, until done —
        // wrapped in try/catch since this can run before the schema/rows exist
        // (e.g. during the very first `migrate` of a fresh install).
        try {
            $this->linkRouteToPlugin();
        } catch (\Throwable $e) {
            // tables/columns not ready yet — safe to ignore, retried on next boot
        }
    }

    private function linkRouteToPlugin(): void
    {
        $route = UserRoute::whereNull('plugin_id')->where('name', self::ROUTE_NAME)->first();
        if (! $route) {
            return;
        }

        $plugin = Plugin::where('name', self::PLUGIN_NAME)->first();
        if (! $plugin) {
            return;
        }

        $route->plugin_id = $plugin->id;
        $route->save();
    }
}
