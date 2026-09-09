# OCR plugin for DiData

Extracts text from an uploaded PDF or image using OpenAI's vision-capable models, called over HTTPS — no Tesseract, Imagick, or Ghostscript required on the server.

## Install

```
composer require swissdidata/ocrplugin
```

Set `OPENAI_API_KEY` in the server's `.env` (same variable DiData's own `Domain\Ai` package uses — if your instance already has AI features configured, it's likely already set). Optionally set `OPENAI_OCR_MODEL` to override the default (`gpt-4o`).

The migration seeds a DiData **User Route**: `POST /api/user-routes-call/ocr_extract_text`.

The package also registers an **OCR module** (`resources/template.xml` + `resources/script.js`) — a screen in DiData with a drag-and-drop dropzone, a language field, and the extracted text shown with a copy button. It calls the route above via `this.dapp.$axios.$post(await this.getRouteURLByName('ocr_extract_text'), formData)`.

## Usage

Send `multipart/form-data`:

- `file` — the PDF or image to OCR
- `lang` — optional free-text hint about the document's language(s), passed into the prompt (e.g. `French, English`)

Response:

```json
{ "file": "scan.pdf", "text": "..." }
```

## Requirements

- `OPENAI_API_KEY` set in `.env`, with access to a vision-capable model (default `gpt-4o`)
- Outbound HTTPS access from the server to `api.openai.com`
