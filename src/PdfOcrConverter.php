<?php

namespace SwissDidata\Ocr;

use Imagick;
use thiagoalessio\TesseractOCR\TesseractOCR;

/**
 * Tesseract only reads raster images, not PDFs, so each page is rasterized
 * via Imagick/Ghostscript before being handed to Tesseract.
 */
class PdfOcrConverter
{
    public function extractText(string $pdfPath, array $languages = ['eng', 'fra']): string
    {
        if (!class_exists(Imagick::class)) {
            throw new \RuntimeException('The Imagick PHP extension (with Ghostscript) is required to OCR PDF files.');
        }

        $imagick = new Imagick();
        $imagick->setResolution(300, 300);
        $imagick->readImage($pdfPath);
        $imagick->setImageFormat('png');

        $pageTexts = [];

        foreach ($imagick as $index => $page) {
            $pagePath = sys_get_temp_dir() . '/ocr_page_' . uniqid() . "_{$index}.png";
            $page->writeImage($pagePath);

            try {
                $pageTexts[] = (new TesseractOCR($pagePath))->lang(...$languages)->run();
            } finally {
                @unlink($pagePath);
            }
        }

        $imagick->clear();

        return implode("\n\n--- page break ---\n\n", $pageTexts);
    }
}
