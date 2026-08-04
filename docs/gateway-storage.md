# Gateway Storage

Gateway Storage keeps private provider metadata behind public IDs. Wasabi is the initial S3-compatible provider; application code depends on `StorageAdapterInterface`, never SDK result types. Objects remain private and are accessed through authorized Gateway routes.

Company Notices use `company_notices/attachments/` and `company_notices/images/`. Automated destructive diagnostics are restricted to `company_notices/test/`. Object keys use a UTC `Ymd_His` timestamp, eight-character cryptographically random uppercase hexadecimal token, and sanitized filename. Keys never overwrite an existing object.

Install locked dependencies and rebuild local Quill assets with:

```text
npm ci
npm run build:vendor
composer install
```

Production deployment includes the generated files under `public/assets/vendor/quill/2.0.3/` and does not require npm. Quill 2.0.3 is distributed under BSD-3-Clause; its unmodified license is retained beside the assets. AWS SDK for PHP v3 is installed through Composer.

Diagnostics:

```text
php bin/gateway upload:check
php bin/gateway storage:check
php bin/gateway storage:test
```

`storage:test` requires local/development mode, both database profiles resolving to `npmgateway_test`, and the exact isolated prefix `company_notices/test/`. It uploads one harmless generated object, verifies metadata and streamed content integrity, deletes it, and confirms absence.

The Wasabi identity should have only the required object permissions for the configured bucket and prefixes: PutObject, GetObject/HeadObject, and DeleteObject. Do not grant public access, bucket deletion, account administration, or unrelated-bucket access.
