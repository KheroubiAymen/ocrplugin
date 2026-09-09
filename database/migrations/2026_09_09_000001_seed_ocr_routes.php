<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\UserRoute;
use App\Services\ResourceService;
use App\Services\UserRoutes\UserRouteService;

return new class extends Migration
{
    private array $routes = [
        [
            'name'       => 'ocr_extract_text',
            'type'       => 'POST',
            'is_enabled' => true,
            'is_public'  => false,
            'sub_path'   => 'ocr_extract_text',
            'code'       => <<<'PHP'
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
PHP,
        ],
    ];

    public function up(): void
    {
        // `migrate` disables permission checks app-wide (App\Listeners\CommandStartingListener),
        // so UserRouteService's authorize() calls pass here even though nobody is logged in.
        foreach ($this->routes as $routeData) {
            $existing = UserRoute::where('name', $routeData['name'])->first();

            $service = $existing
                ? app()->make(UserRouteService::class, [
                    'data'             => $routeData,
                    'operation'        => ResourceService::UPDATE_OPERATION,
                    'resourceToUpdate' => $existing,
                ])
                : app()->make(UserRouteService::class, ['data' => $routeData]);

            $service->consume();
        }
    }

    public function down(): void
    {
        $names = array_column($this->routes, 'name');
        UserRoute::whereIn('name', $names)->delete();
    }
};
