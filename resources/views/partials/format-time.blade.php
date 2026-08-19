@php
    /**
     * Format minutes into a readable time string.
     * Examples:
     *   1.5 minutes  → 01:30
     *   10 minutes   → 10:00
     *   65 minutes   → 01:05:00
     */
    if (! function_exists('format_time_taken_seconds')) {
        function format_time_taken_seconds($seconds)
        {
            $seconds = (int) round(max(0, (float) $seconds));
            $hours = intdiv($seconds, 3600);
            $minutes = intdiv($seconds % 3600, 60);
            $secs = $seconds % 60;

            if ($hours > 0) {
                return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
            }

            return sprintf('%02d:%02d', $minutes, $secs);
        }
    }

    if (! function_exists('format_time_taken')) {
        function format_time_taken($attempt)
        {
            if ($attempt?->submitted_at && $attempt->started_at) {
                // Carbon 3's diffInSeconds() returns a signed value.
                // Since submitted_at is always after started_at, the result
                // is negative. Use abs() to get the correct positive duration.
                $seconds = abs($attempt->submitted_at->diffInSeconds($attempt->started_at));
                return format_time_taken_seconds($seconds);
            }

            return '00:00';
        }
    }
@endphp