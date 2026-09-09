# OCR plugin for DiData

Extracts text from an uploaded PDF or image using Tesseract OCR (must already be installed on the server: `tesseract-ocr` binary + language data, e.g. `tesseract-ocr-fra`).

## Install

```
composer require thiagoalessio/tesseract_ocr
composer require swissdidata/ocrplugin
```

The migration seeds a DiData **User Route**: `POST /api/user-routes-call/ocr_extract_text`.

## Usage

Send `multipart/form-data`:

- `file` — the PDF or image to OCR
- `lang` — optional, comma-separated Tesseract language codes (default `eng,fra`)

Response:

```json
{ "file": "scan.pdf", "text": "..." }
```

PDF files are rasterized page by page via Imagick (requires the `imagick` PHP extension + Ghostscript) before OCR, since Tesseract only reads raster images. Pages are joined with `--- page break ---`.

## Requirements

- `tesseract-ocr` binary installed on the server, with the language packs used in `lang`
- PHP `imagick` extension + Ghostscript, for PDF input
