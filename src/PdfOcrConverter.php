<?php

namespace SwissDidata\Ocr;

use Imagick;
use thiagoalessio\TesseractOCR\TesseractOCR;

/**
 * Tesseract only reliably reads raster images, not PDFs. When Imagick is
 * available, each page is rasterized (via Ghostscript) before OCR. Otherwise
 * we fall back to handing the PDF straight to the tesseract binary — many
 * builds link against a PDF-capable leptonica and can read it directly, with
 * no extra system dependency beyond what thiagoalessio/tesseract_ocr already
 * requires.
 */
class PdfOcrConverter
{
    public function extractText(string $pdfPath, array $languages = ['eng', 'fra']): string
    {
        if (class_exists(Imagick::class)) {
            return $this->extractViaImagick($pdfPath, $languages);
        }

        try {
            return (new TesseractOCR($pdfPath))->lang(...$languages)->run();
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'Could not OCR this PDF: the Imagick PHP extension is not installed, and passing the PDF '
                .'directly to Tesseract failed ('.$e->getMessage().'). Install php-imagick + ghostscript '
                .'on the server, or use a Tesseract build with built-in PDF support.'
            );
        }
    }

    private function extractViaImagick(string $pdfPath, array $languages): string
    {
        $imagick = new Imagick();
        $imagick->setResolution(300, 300);
        $imagick->readImage($pdfPath);
        $imagick->setImageFormat('png');

        $pageTexts = [];

        foreach ($imagick as $index => $page) {
            $pagePath = sys_get_temp_dir().'/ocr_page_'.uniqid()."_{$index}.png";
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
