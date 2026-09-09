# OCR plugin for DiData

Extracts text from an uploaded PDF or image using Tesseract OCR (must already be installed on the server: `tesseract-ocr` binary + language data, e.g. `tesseract-ocr-fra`).

## Install

```
composer require thiagoalessio/tesseract_ocr
composer require swissdidata/ocrplugin
```

The migration seeds a DiData **User Route**: `POST /api/user-routes-call/ocr_extract_text`.

The package also registers an **OCR module** (`resources/template.xml` + `resources/script.js`) — a screen in DiData with a drag-and-drop dropzone, a language field, and the extracted text shown with a copy button. It calls the route above via `this.dapp.$axios.$post(await this.getRouteURLByName('ocr_extract_text'), formData)`.

## Usage

Send `multipart/form-data`:

- `file` — the PDF or image to OCR
- `lang` — optional, comma-separated Tesseract language codes (default `eng,fra`)

Response:

```json
{ "file": "scan.pdf", "text": "..." }
```

For PDFs: if the `imagick` PHP extension is installed, each page is rasterized (via Ghostscript) then OCR'd, with pages joined by `--- page break ---`. If Imagick isn't installed, the PDF is handed directly to the `tesseract` binary instead — this works if the server's Tesseract build has PDF support built in (via leptonica), with no extra dependency. If neither works, the route returns a clear error explaining what's missing.

## Requirements

- `tesseract-ocr` binary installed on the server, with the language packs used in `lang`
- For PDFs: either the PHP `imagick` extension + Ghostscript, or a Tesseract build with built-in PDF support
