<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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

$extension = strtolower($uploaded->getClientOriginalExtension());
$path      = $uploaded->getRealPath();
$languages = array_filter(explode(',', request()->input('lang', 'eng,fra')));

try {
    if ($extension === 'pdf') {
        $text = (new SwissDidata\Ocr\PdfOcrConverter())->extractText($path, $languages);
    } else {
        $text = (new thiagoalessio\TesseractOCR\TesseractOCR($path))->lang(...$languages)->run();
    }

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
        $now = now();
        foreach ($this->routes as $routeData) {
            $existing = DB::table('user_route')->where('name', $routeData['name'])->first();
            if ($existing) {
                DB::table('user_route')->where('name', $routeData['name'])->update([
                    'type'       => $routeData['type'],
                    'code'       => $routeData['code'],
                    'is_enabled' => $routeData['is_enabled'] ? 1 : 0,
                    'sub_path'   => $routeData['sub_path'],
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('user_route')->insert([
                    'name'                        => $routeData['name'],
                    'type'                        => $routeData['type'],
                    'code'                        => $routeData['code'],
                    'is_enabled'                  => $routeData['is_enabled'] ? 1 : 0,
                    'is_public'                   => $routeData['is_public'] ? 1 : 0,
                    'sub_path'                    => $routeData['sub_path'],
                    'allow_all_users_access_list' => 1,
                    'created_at'                  => $now,
                    'updated_at'                  => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $names = array_column($this->routes, 'name');
        DB::table('user_route')->whereIn('name', $names)->delete();
    }
};
