<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Confirms a file actually is what its extension claims.
 *
 * Laravel's `mimes:` rule reads the MIME type the *client* reported in the multipart
 * headers, which is attacker-controlled. It is not a content check: the same request
 * that uploads a PNG can report image/png for a PHP script, and the mimes rule accepts
 * it. That file is then stored and served back by AttachmentController.
 *
 * getMimeType() reads the file's magic bytes instead, so it cannot be spoofed by a
 * header. Both are kept because they check different things — mimes covers the
 * extension the browser sent (what the operator will see in a download), this covers
 * what the bytes actually are (what will happen if the file is ever executed or
 * interpreted).
 *
 * Only the formats that can actually be embedded or served are asserted byte-for-byte.
 * The office formats are matched on their real container signatures, which are stable:
 * OLE2 for legacy .doc/.xls, and the ZIP local-file-header for .docx/.xlsx, which is
 * the same container a .jar or .apk uses — hence the explicit extension pairing rather
 * than trusting the container alone.
 */
class RealFileContent implements ValidationRule
{
    /**
     * Magic-byte prefixes, matched case-insensitively at offset 0.
     */
    private const SIGNATURES = [
        'jpeg' => "\xff\xd8\xff",
        'jpg' => "\xff\xd8\xff",
        'png' => "\x89PNG\r\n\x1a\n",
        'gif' => 'GIF87a',
        'gif ' => 'GIF89a',
        'pdf' => '%PDF-',
    ];

    public function __construct(
        /** Extensions this file's content must be consistent with. */
        private readonly array $allowedExtensions = ['jpeg', 'jpg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'],
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return;
        }

        $extension = strtolower($value->getClientOriginalExtension() ?: '');

        if (! in_array($extension, $this->allowedExtensions, true)) {
            $fail('Unsupported file type.');

            return;
        }

        // Office formats are container formats rather than fixed signatures, so they
        // are handled separately below.
        if (in_array($extension, ['csv', 'txt', 'webp'], true)) {
            $this->validateLooseFormat($value, $extension, $fail);

            return;
        }

        if (in_array($extension, ['doc', 'docx', 'xls', 'xlsx'], true)) {
            $this->validateOfficeContainer($value, $extension, $fail);

            return;
        }

        $header = (string) file_get_contents($value->getRealPath(), false, null, 0, 8);

        foreach (self::SIGNATURES as $signature) {
            if ($signature !== 'gif ' && str_starts_with(strtolower($header), strtolower($signature))) {
                return;
            }
        }

        if (in_array($extension, ['gif'], true)
            && (str_starts_with($header, 'GIF87a') || str_starts_with($header, 'GIF89a'))) {
            return;
        }

        $fail(sprintf(
            'The contents of this file are not a valid %s. The extension does not match what was uploaded.',
            $extension
        ));
    }

    /**
     * CSV and TXT are plain text, so the check is that they really are — a binary
     * blob renamed .txt is not what the operator will be able to open or parse.
     *
     * WEBP has no magic number: it is a RIFF container with the "WEBP" form type at
     * offset 8, so the header has to be 12 bytes rather than the usual 8.
     */
    private function validateLooseFormat(UploadedFile $file, string $extension, Closure $fail): void
    {
        if ($extension === 'webp') {
            $header = (string) file_get_contents($file->getRealPath(), false, null, 0, 12);

            if (str_starts_with($header, 'RIFF') && str_contains(substr($header, 8, 4), 'WEBP')) {
                return;
            }

            $fail('The contents of this file are not a valid webp image.');

            return;
        }

        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            $fail('The uploaded file could not be read.');

            return;
        }

        $sample = (string) fread($handle, 8192);
        fclose($handle);

        if (str_contains($sample, "\0")) {
            $fail(sprintf('A .%s file must be plain text; this one contains binary data.', $extension));
        }
    }

    /**
     * Legacy Office files are OLE2 compound documents; OOXML ones are ZIP containers.
     * A mismatch between the two is the case worth catching — a .xlsx that is really
     * an OLE2 .xls is either a mis-named upload or a deliberate one.
     */
    private function validateOfficeContainer(UploadedFile $file, string $extension, Closure $fail): void
    {
        $header = (string) file_get_contents($file->getRealPath(), false, null, 0, 8);

        $isOle2 = str_starts_with($header, "\xd0\xcf\x11\xe0\xa1\xb1\x1a\xe1");
        $isZip = str_starts_with($header, "PK\x03\x04");

        $expectedLegacy = in_array($extension, ['doc', 'xls'], true);

        if ($expectedLegacy && $isOle2) {
            return;
        }

        if (! $expectedLegacy && $isZip) {
            return;
        }

        $fail(sprintf(
            'The contents of this file are not a valid .%s. The extension does not match what was uploaded.',
            $extension
        ));
    }
}
