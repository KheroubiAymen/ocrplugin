<?php

namespace SwissDidata\Ocr;

use App\Models\UserRoute;
use Didata\Packages\installer\Package;
use Didata\Packages\installer\PackageInstaller;

class OcrPackage extends PackageInstaller
{
    private const ROUTE_NAME = 'ocr_extract_text';

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

    public function boot()
    {
        parent::boot();

        // Laravel never re-runs a migration once it's been applied, even after
        // its content changes — so bumping the route's PHP script would
        // otherwise need a brand new migration file for every single tweak
        // (see database/migrations/2026_09_09_000002_...). Instead, keep the
        // route's `code` column self-synced to the package's current source
        // of truth (below) on every boot. A direct Eloquent update, not
        // UserRouteService — that service's authorize() calls are meant for
        // an admin acting through the UI, and would fail for whichever
        // regular user happens to trigger this on a normal page load.
        try {
            $this->syncRouteCode();
        } catch (\Throwable $e) {
            // table/row not ready yet (e.g. before the first `migrate`) — safe to ignore
        }
    }

    private function syncRouteCode(): void
    {
        $route = UserRoute::where('name', self::ROUTE_NAME)->first();
        if (! $route) {
            return;
        }

        $currentCode = self::routeCode();
        if ($route->code !== $currentCode) {
            $route->code = $currentCode;
            $route->save();
        }
    }

    public static function routeCode(): string
    {
        return <<<'PHP'
$this->response->setResponseHeaders(['content-type' => 'application/json']);

$uploaded = request()->file('file');
if (!$uploaded) {
    $this->response->setResponseContent(json_encode([
        'error' => 'No file uploaded. Send multipart/form-data with a "file" field (PDF, PNG, JPG, TIFF...).',
    ]));
    return;
}

\Log::info('[OCR] Extracting text from uploaded file: ' . $uploaded->getClientOriginalName());

$languageHint = trim((string) request()->input('lang', ''));

try {
    $text = (new SwissDidata\Ocr\OpenAiOcrService())->extractText(
        $uploaded->getRealPath(),
        $uploaded->getMimeType(),
        $languageHint ?: null
    );

    $this->response->setResponseContent(json_encode([
        'file' => $uploaded->getClientOriginalName(),
        'text' => $text,
    ]));
} catch (\Throwable $e) {
    \Log::error('[OCR] Extraction failed: ' . $e->getMessage());
    $this->response->setResponseContent(json_encode([
        'error' => 'OCR extraction failed: ' . $e->getMessage(),
    ]));
}
PHP;
    }
}
