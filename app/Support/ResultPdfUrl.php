<?php

namespace App\Support;

use App\Models\ExamAttempt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ResultPdfUrl
{
    public static function make(ExamAttempt $attempt, string $token): string
    {
        $baseUrl = self::baseUrl();

        return $baseUrl.route('result-pdfs.show', [
            'attempt' => $attempt,
            'token' => $token,
        ], false);
    }

    public static function baseUrl(): string
    {
        $candidates = self::baseUrlCandidates();

        foreach ($candidates as $candidate) {
            if (self::isPublicHttpsBaseUrl($candidate['url'])) {
                return rtrim($candidate['url'], '/');
            }
        }

        foreach ($candidates as $candidate) {
            $httpsUrl = self::withHttpsScheme($candidate['url']);

            if (self::isPublicHttpsBaseUrl($httpsUrl)) {
                Log::info('Using HTTPS public host for result PDF URL.', [
                    'source' => $candidate['source'],
                    'configured_url' => $candidate['url'],
                    'public_base_url' => $httpsUrl,
                ]);

                return rtrim($httpsUrl, '/');
            }
        }

        $fallback = rtrim(trim((string) config('app.url')), '/');

        Log::warning('Could not derive a public HTTPS base URL for result PDF.', [
            'fallback' => $fallback,
            'candidates' => $candidates,
        ]);

        return $fallback;
    }

    /**
     * @return array<int, array{source: string, url: string}>
     */
    public static function baseUrlCandidates(): array
    {
        $candidates = [];

        self::addCandidate($candidates, trim((string) config('services.zoho.result_pdf_base_url')), 'ZOHO_RESULT_PDF_BASE_URL');

        if (app()->bound('request')) {
            $forwardedProto = self::firstHeaderValue((string) request()->headers->get('X-Forwarded-Proto', ''));
            $forwardedHost = self::normalizeHost(self::firstHeaderValue((string) request()->headers->get('X-Forwarded-Host', '')));
            self::addCandidate(
                $candidates,
                $forwardedProto && $forwardedHost ? "{$forwardedProto}://{$forwardedHost}" : '',
                'X-Forwarded-Proto/X-Forwarded-Host'
            );

            $forwarded = (string) request()->headers->get('Forwarded', '');
            self::addCandidate($candidates, self::baseUrlFromForwardedHeader($forwarded), 'Forwarded');

            self::addCandidate($candidates, request()->getSchemeAndHttpHost(), 'request');
            self::addCandidate($candidates, self::httpsBaseUrlForHost(request()->getHost()), 'request_host_https');
        }

        self::addCandidate($candidates, trim((string) config('app.url')), 'APP_URL');

        return $candidates;
    }

    /**
     * @return array<string, mixed>
     */
    public static function diagnosticsForPath(string $path, string $generatedUrl): array
    {
        $disk = Storage::disk('public');
        $filesystemPath = $disk->path($path);
        $storageUrl = $disk->url($path);

        return [
            'pdf_storage_disk' => 'public',
            'pdf_storage_path' => $path,
            'pdf_filesystem_path' => $filesystemPath,
            'pdf_file_exists' => file_exists($filesystemPath),
            'pdf_disk_exists' => $disk->exists($path),
            'pdf_file_permissions' => file_exists($filesystemPath) ? substr(sprintf('%o', fileperms($filesystemPath)), -4) : null,
            'generated_pdf_url' => $generatedUrl,
            'generated_pdf_url_starts_with_https' => str_starts_with($generatedUrl, 'https://'),
            'app_url' => config('app.url'),
            'zoho_result_pdf_base_url' => config('services.zoho.result_pdf_base_url'),
            'storage_url' => $storageUrl,
            'asset_storage_url' => asset($storageUrl),
            'public_storage_link_exists' => file_exists(public_path('storage')),
            'public_storage_link_target' => is_link(public_path('storage')) ? readlink(public_path('storage')) : null,
            'filesystem_public_root' => config('filesystems.disks.public.root'),
            'filesystem_public_url' => config('filesystems.disks.public.url'),
            'base_url_candidates' => self::baseUrlCandidates(),
        ];
    }

    private static function isPublicHttpsBaseUrl(string $url): bool
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme !== 'https' || $host === '') {
            return false;
        }

        return self::isPublicHost($host);
    }

    private static function isPublicHost(string $host): bool
    {
        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.local')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false
            && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        return true;
    }

    private static function addCandidate(array &$candidates, string $url, string $source): void
    {
        $url = rtrim(trim($url), '/');

        if ($url === '') {
            return;
        }

        $candidates[] = [
            'source' => $source,
            'url' => $url,
        ];
    }

    private static function withHttpsScheme(string $url): string
    {
        $parts = parse_url($url);
        $host = self::normalizeHost((string) ($parts['host'] ?? ''));

        if ($host === '' || ! self::isPublicHost($host)) {
            return '';
        }

        $port = isset($parts['port']) && (int) $parts['port'] !== 443 ? ':'.$parts['port'] : '';

        return 'https://'.$host.$port;
    }

    private static function httpsBaseUrlForHost(string $host): string
    {
        $host = self::normalizeHost($host);

        if (! self::isPublicHost($host)) {
            return '';
        }

        return 'https://'.$host;
    }

    private static function baseUrlFromForwardedHeader(string $header): string
    {
        if ($header === '') {
            return '';
        }

        $firstValue = trim(explode(',', $header)[0]);
        $parts = [];

        foreach (explode(';', $firstValue) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            $parts[strtolower($key)] = trim($value, '"');
        }

        if (empty($parts['proto']) || empty($parts['host'])) {
            return '';
        }

        $proto = strtolower(self::firstHeaderValue($parts['proto']));
        $host = self::normalizeHost($parts['host']);

        return $proto && $host ? "{$proto}://{$host}" : '';
    }

    private static function firstHeaderValue(string $value): string
    {
        return strtolower(trim(explode(',', $value)[0]));
    }

    private static function normalizeHost(string $host): string
    {
        return strtolower(trim(explode(',', $host)[0]));
    }
}
