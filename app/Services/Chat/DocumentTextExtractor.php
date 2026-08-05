<?php

namespace App\Services\Chat;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Pulls plain text out of uploaded knowledge-base files so agents and the
 * auto-reply bot can search them without a separate search index.
 */
class DocumentTextExtractor
{
    public function extract(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));

        return match ($extension) {
            'txt', 'md', 'csv', 'log' => $this->fromPlainText($file),
            'docx' => $this->fromDocx($file),
            'pdf' => $this->fromPdf($file),
            default => $this->fromPlainText($file),
        };
    }

    public function extractFromStored(string $disk, string $path, string $extension): string
    {
        $contents = Storage::disk($disk)->get($path);
        if ($contents === null || $contents === '') {
            return '';
        }

        $tmp = tempnam(sys_get_temp_dir(), 'kbdoc');
        file_put_contents($tmp, $contents);

        try {
            $uploaded = new UploadedFile($tmp, 'file.'.$extension, null, null, true);

            return $this->extract($uploaded);
        } finally {
            @unlink($tmp);
        }
    }

    protected function fromPlainText(UploadedFile $file): string
    {
        $text = @file_get_contents($file->getRealPath()) ?: '';

        return $this->normalize($text);
    }

    protected function fromDocx(UploadedFile $file): string
    {
        if (! class_exists(ZipArchive::class)) {
            return '';
        }

        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();

        $text = strip_tags(str_replace(['</w:p>', '</w:tr>'], "\n", $xml));

        return $this->normalize(html_entity_decode($text, ENT_QUOTES | ENT_XML1));
    }

    /**
     * Lightweight PDF text scrape — good enough for text-based PDFs without
     * pulling in a heavyweight parser dependency.
     */
    protected function fromPdf(UploadedFile $file): string
    {
        $raw = @file_get_contents($file->getRealPath()) ?: '';
        $chunks = [];

        if (preg_match_all('/\((\\\\.|[^\\\\)])*\)/s', $raw, $matches)) {
            foreach ($matches[0] as $match) {
                $inner = substr($match, 1, -1);
                $inner = str_replace(['\\n', '\\r', '\\t', '\\(', '\\)', '\\\\'], ["\n", "\r", "\t", '(', ')', '\\'], $inner);
                if (preg_match('/[A-Za-z0-9]/', $inner)) {
                    $chunks[] = $inner;
                }
            }
        }

        if (preg_match_all('/stream\s*(.*?)\s*endstream/si', $raw, $streams)) {
            foreach ($streams[1] as $stream) {
                $decoded = @gzuncompress($stream) ?: @gzinflate($stream) ?: '';
                if ($decoded && preg_match('/[A-Za-z]{3,}/', $decoded)) {
                    $chunks[] = preg_replace('/[^\x20-\x7E\n\r\t]/', ' ', $decoded);
                }
            }
        }

        return $this->normalize(implode("\n", $chunks));
    }

    protected function normalize(string $text): string
    {
        $text = preg_replace("/\r\n?/", "\n", $text) ?? $text;
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim(mb_substr($text, 0, 200000));
    }
}
