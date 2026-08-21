<?php

use App\Models\PassageGroup;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Passage/summary content is now rendered as raw HTML (it comes from
     * CKEditor). Rows saved before HtmlSanitizer existed were never
     * sanitized, so re-run them through it now rather than trusting
     * whatever was stored while the editor had no server-side filtering.
     */
    public function up(): void
    {
        PassageGroup::query()->whereNotNull('content')->each(function (PassageGroup $passageGroup): void {
            $passageGroup->updateQuietly([
                'content' => HtmlSanitizer::sanitize($passageGroup->content),
            ]);
        });
    }

    public function down(): void
    {
        // Not reversible: the original unsanitized content isn't recoverable.
    }
};
