<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Summaries are now called Passages. Only the auto-generated titles
     * ("Summary 1", "Summary 2", ...) are renamed; passage content and any
     * other title are left exactly as they are.
     */
    public function up(): void
    {
        $this->rename('Summary', 'Passage');
    }

    public function down(): void
    {
        $this->rename('Passage', 'Summary');
    }

    private function rename(string $from, string $to): void
    {
        DB::table('passage_groups')->where('title', 'like', $from.' %')->orderBy('id')->get(['id', 'title'])
            ->each(function ($group) use ($from, $to): void {
                if (preg_match('/^'.$from.' (\d+)$/', $group->title, $matches) === 1) {
                    DB::table('passage_groups')->where('id', $group->id)->update(['title' => $to.' '.$matches[1]]);
                }
            });
    }
};
