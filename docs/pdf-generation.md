# PDF Generation

Gateway uses Composer-managed `dompdf/dompdf` v3.1.6 as its standard in-process PDF engine. Future report modules should depend on `GatewayPdfRendererInterface` and reuse `DompdfGatewayPdfRenderer`; controllers must not instantiate Dompdf directly.

The renderer accepts a controlled `GatewayPdfDocument` containing trusted server-rendered HTML and approved page settings. It renders to memory and returns PDF bytes. Remote resources and JavaScript are disabled, local access is chrooted to the dedicated PDF-template directory, and templates are self-contained. Reports use bundled DejaVu Sans for UTF-8 names, punctuation, bullets, and currency. No custom fonts, external APIs, browser executable, public file, Wasabi object, or persistent report artifact is involved.

Each consuming endpoint independently authenticates and authorizes the request, validates authoritative criteria, and returns `private, no-store` PDF responses. Templates must escape ordinary fields and may render only content already approved for trusted HTML presentation. Renderer failures return safe errors and log only report identity, a failure code, and exception class.
