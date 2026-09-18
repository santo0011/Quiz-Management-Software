<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResultPdfUrlVerifier
{
    private ?string $lastFailureMessage = null;

    public function lastFailureMessage(): ?string
    {
        return $this->lastFailureMessage;
    }

    public function verify(string $url): bool
    {
        $this->lastFailureMessage = null;

        if (! $this->isHttpsPublicUrl($url)) {
            $this->lastFailureMessage = 'Result PDF URL must be a complete public HTTPS URL.';

            Log::warning('Result PDF URL is not a complete public HTTPS URL.', [
                'url' => $url,
            ]);

            return false;
        }

        try {
            $response = Http::accept('application/pdf')
                ->withoutRedirecting()
                ->timeout(10)
                ->get($url);
        } catch (\Throwable $e) {
            $this->lastFailureMessage = 'Result PDF URL could not be reached publicly: '.$e->getMessage();

            Log::warning('Result PDF public URL verification failed.', [
                'url' => $url,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }

        $contentType = strtolower($response->header('Content-Type', ''));

        Log::info('Result PDF public URL verification response.', [
            'url' => $url,
            'http_status' => $response->status(),
            'content_type' => $contentType,
            'starts_with_pdf_signature' => str_starts_with($response->body(), '%PDF'),
        ]);

        if (! $response->ok()) {
            $this->lastFailureMessage = 'Result PDF URL returned HTTP '.$response->status().'.';

            return false;
        }

        if (! str_contains($contentType, 'application/pdf')) {
            $this->lastFailureMessage = 'Result PDF URL did not return application/pdf.';

            return false;
        }

        if (! str_starts_with($response->body(), '%PDF')) {
            $this->lastFailureMessage = 'Result PDF URL response was not a valid PDF file.';

            return false;
        }

        return true;
    }

    private function isHttpsPublicUrl(string $url): bool
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme !== 'https' || $host === '') {
            return false;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.local')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false
            && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        return true;
    }
}
