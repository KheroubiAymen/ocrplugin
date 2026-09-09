<?php

namespace SwissDidata\Ocr;

use Illuminate\Support\Facades\Http;

/**
 * OCR via OpenAI's vision-capable models, called over HTTP — no native
 * Tesseract/Imagick binary required on the server. Reads OPENAI_API_KEY from
 * .env, the same variable DiData's own Domain\Ai package uses.
 */
class OpenAiOcrService
{
    private string $apiKey;

    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) env('OPENAI_API_KEY', '');
        $this->model = (string) env('OPENAI_OCR_MODEL', 'gpt-4o');

        if ($this->apiKey === '') {
            throw new \RuntimeException('OPENAI_API_KEY is not set in .env — required to run OCR via OpenAI.');
        }
    }

    public function extractText(string $filePath, string $mimeType, ?string $languageHint = null): string
    {
        $base64 = base64_encode(file_get_contents($filePath));
        $isPdf = $mimeType === 'application/pdf';

        $prompt = 'Extract all text from this document, verbatim, preserving line breaks and reading order. '
            .'Return only the extracted text, no commentary, no markdown formatting.';
        if ($languageHint) {
            $prompt .= " The document may contain these languages: {$languageHint}.";
        }

        $content = [
            ['type' => 'text', 'text' => $prompt],
        ];

        if ($isPdf) {
            $content[] = [
                'type' => 'file',
                'file' => [
                    'filename' => basename($filePath).'.pdf',
                    'file_data' => "data:application/pdf;base64,{$base64}",
                ],
            ];
        } else {
            $content[] = [
                'type' => 'image_url',
                'image_url' => ['url' => "data:{$mimeType};base64,{$base64}"],
            ];
        }

        $response = Http::withToken($this->apiKey)
            ->timeout(120)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => $content],
                ],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('OpenAI API error: '.$response->body());
        }

        return trim((string) $response->json('choices.0.message.content'));
    }
}
