{{--
    Per-exam attempt history: every submitted attempt of one Student at one
    Exam. Clicking an attempt's view button slides open that attempt's own
    result (marks, percentage, question performance) right under its row.

    @param  \Illuminate\Support\Collection  $history   submitted attempts, oldest first
    @param  \App\Models\ExamAttempt         $current   the attempt being viewed
    @param  \Closure                        $routeFor  fn (ExamAttempt $attempt): string
--}}
@include('partials.format-time')
@php($maxAttempts = max((int) ($current->exam?->maximum_attempts ?? 1), (int) $history->max('attempt_number')))

@if ($history->count() > 1 || $maxAttempts > 1)
    @php($history->loadMissing('answers.selectedOption'))
    @php($historyOrderedItems = $current->exam?->orderedItems() ?? collect())
    @php($accordionId = 'attemptHistory'.$current->id)

    <section class="{{ $sectionClass ?? 'content-panel' }} attempt-history">
        <div class="{{ $headerClass ?? 'panel-header' }}">
            <div>
                <h2><i class="bi bi-clock-history me-2 text-primary"></i>Attempt History</h2>
                <p>{{ $history->count() }} of {{ $maxAttempts }} {{ Str::plural('attempt', $maxAttempts) }} completed. Click <i class="bi bi-eye-fill"></i> to open an attempt's answer review.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle admin-table attempt-history-table">
                <thead>
                    <tr>
                        <th>Attempt</th>
                        <th>Marks</th>
                        <th>Percentage</th>
                        <th>Correct</th>
                        <th>Incorrect</th>
                        <th>Not Attempted</th>
                        <th>Submitted</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody id="{{ $accordionId }}">
                    @foreach ($history as $item)
                        @php($detailId = $accordionId.'-'.$item->id)
                        <tr class="attempt-history-row {{ $item->id === $current->id ? 'is-current' : '' }}">
                            <td>
                                <span class="attempt-chip">{{ $item->attempt_number }}/{{ $maxAttempts }}</span>
                                @if ($item->id === $current->id)
                                    <span class="attempt-viewing">Viewing</span>
                                @endif
                            </td>
                            <td><strong>{{ $item->obtained_marks }}</strong> / {{ $current->exam?->total_marks }}</td>
                            <td>{{ $item->percentage }}%</td>
                            <td class="text-success fw-semibold">{{ $item->correct_count }}</td>
                            <td class="text-danger fw-semibold">{{ $item->wrong_count }}</td>
                            <td>{{ $item->unanswered_count }}</td>
                            <td>{{ $item->submitted_at?->format('d-m-Y H:i:s') }}</td>
                            <td class="text-end">
                                <button type="button"
                                        class="btn btn-sm btn-soft attempt-open-btn collapsed"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#{{ $detailId }}"
                                        aria-expanded="false"
                                        aria-controls="{{ $detailId }}"
                                        title="View attempt {{ $item->attempt_number }} answer review">
                                    <i class="bi bi-eye-fill attempt-open-icon"></i>
                                    <i class="bi bi-chevron-up attempt-close-icon"></i>
                                </button>
                            </td>
                        </tr>
                        <tr class="attempt-detail-row">
                            <td colspan="8">
                                <div class="collapse" id="{{ $detailId }}" data-bs-parent="#{{ $accordionId }}">
                                    <div class="attempt-detail">
                                        <div class="attempt-detail-head">
                                            <h3><i class="bi bi-list-check me-2 text-primary"></i>Attempt {{ $item->attempt_number }}/{{ $maxAttempts }} · Answer Review</h3>
                                            <span class="question-count-badge">
                                                {{ $item->obtained_marks }} / {{ $current->exam?->total_marks }} marks · {{ $item->percentage }}%
                                            </span>
                                        </div>

                                        @include('results.partials.answer-review-list', [
                                            'attempt' => $item,
                                            'orderedItems' => $historyOrderedItems,
                                            'selectedLabel' => $selectedLabel ?? null,
                                        ])
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
