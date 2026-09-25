{{--
    Admin-configured site name + logo for PDF headers. Dompdf doesn't fetch
    remote URLs, so the logo is inlined as a data URI from the public disk.
--}}
@php
    $pdfSettings = \App\Models\Setting::current();
    $pdfSiteName = \App\Models\Setting::siteName();
    $pdfLogo = null;

    if ($pdfSettings->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($pdfSettings->logo_path)) {
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $pdfLogo = 'data:'.$disk->mimeType($pdfSettings->logo_path).';base64,'.base64_encode($disk->get($pdfSettings->logo_path));
    }
@endphp
<table class="brand-row">
    <tr>
        @if ($pdfLogo)
            <td class="brand-logo-cell">
                <div class="brand-logo-box"><img src="{{ $pdfLogo }}" alt="{{ $pdfSiteName }} logo" class="brand-logo"></div>
            </td>
        @endif
        <td>
            <h1>{{ $pdfSiteName }}</h1>
            <p>{{ $subtitle }}</p>
        </td>
    </tr>
</table>
