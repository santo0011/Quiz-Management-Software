import React, { useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';

// 0.75 is the smallest step — still legible (Summary/Question text both stay
// well above ~10.5px at that scale) but noticeably more compact than the
// previous 0.85 floor for students who want more of the exam on screen.
const FONT_STEPS = [0.75, 0.85, 1, 1.15, 1.3, 1.45];
const FONT_STEP_STORAGE_KEY = 'quizcore.student.examFontStep';

// Keep in sync with the $(max-width) breakpoint that switches the passage
// layout to a single stacked column in admin.css.
const PASSAGE_MOBILE_QUERY = '(max-width: 767.98px)';
// Same value as .passage-resize-handle's flex-basis in admin.css.
const PASSAGE_DIVIDER_WIDTH = 20;
const PASSAGE_PANEL_MIN_FRACTION = 0.22;
const PASSAGE_PANEL_MAX_FRACTION = 0.5;
const PASSAGE_PANEL_DEFAULT_FRACTION = 0.38;

function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
}

function useMediaQuery(query) {
    const [matches, setMatches] = useState(() => (
        typeof window !== 'undefined' && typeof window.matchMedia === 'function'
            ? window.matchMedia(query).matches
            : false
    ));

    useEffect(() => {
        if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') return;
        const mql = window.matchMedia(query);
        const handleChange = (event) => setMatches(event.matches);
        setMatches(mql.matches);
        mql.addEventListener('change', handleChange);
        return () => mql.removeEventListener('change', handleChange);
    }, [query]);

    return matches;
}

function loadStoredFontStep() {
    // Stored as the actual scale value (e.g. "1.15"), not its array index —
    // an index would silently point at the wrong step whenever FONT_STEPS
    // changes shape (as it just did, adding a smaller step at the front).
    const defaultIndex = FONT_STEPS.indexOf(1);
    try {
        const stored = parseFloat(window.localStorage.getItem(FONT_STEP_STORAGE_KEY));
        const index = FONT_STEPS.indexOf(stored);
        return index === -1 ? defaultIndex : index;
    } catch {
        return defaultIndex;
    }
}

function formatTime(seconds) {
    const value = Math.max(0, seconds);
    const minutes = Math.floor(value / 60).toString().padStart(2, '0');
    const remainingSeconds = Math.floor(value % 60).toString().padStart(2, '0');
    return `${minutes}:${remainingSeconds}`;
}

// Every top-level "step" is either a standalone question or a passage/summary
// group (which is shown as a single step containing all of its questions).
function flattenQuestions(steps) {
    return steps.flatMap((step) => (step.type === 'question' ? [step.question] : step.questions));
}

function buildQuestionStepIndex(steps) {
    const map = {};
    steps.forEach((step, index) => {
        if (step.type === 'question') {
            map[step.question.id] = index;
        } else {
            step.questions.forEach((question) => {
                map[question.id] = index;
            });
        }
    });
    return map;
}

function updateStepsWithAnswer(steps, questionId, optionId) {
    return steps.map((step) => {
        if (step.type === 'question') {
            return step.question.id === questionId
                ? { ...step, question: { ...step.question, selected_option_id: optionId } }
                : step;
        }

        return {
            ...step,
            questions: step.questions.map((question) => (
                question.id === questionId ? { ...question, selected_option_id: optionId } : question
            )),
        };
    });
}

function QuestionOptions({ question, onSelect }) {
    return (
        <div className="exam-options">
            {question.options.map((option) => (
                <button
                    type="button"
                    key={option.id}
                    className={question.selected_option_id === option.id ? 'exam-option selected' : 'exam-option'}
                    onClick={() => onSelect(question.id, option.id)}
                >
                    <span></span>
                    <div className="math-content">{option.text}</div>
                </button>
            ))}
        </div>
    );
}

function PassageStep({ step, questionNumbers, onSelect }) {
    const [collapsed, setCollapsed] = useState(false);
    const [isDragging, setIsDragging] = useState(false);
    const isMobile = useMediaQuery(PASSAGE_MOBILE_QUERY);

    const gridRef = useRef(null);
    const panelRef = useRef(null);
    // The current split ratio, kept in a ref (not state) so every pointer-move
    // during a drag can update the panel's width directly via the DOM instead
    // of going through a React re-render each time — that's what keeps the
    // drag feeling as smooth as a native textarea resize handle.
    const fractionRef = useRef(PASSAGE_PANEL_DEFAULT_FRACTION);
    const rafRef = useRef(null);
    const pendingClientXRef = useRef(null);

    const applyPanelWidth = (fraction) => {
        const grid = gridRef.current;
        const panel = panelRef.current;
        if (!grid || !panel) return;
        const usableWidth = grid.clientWidth - PASSAGE_DIVIDER_WIDTH;
        if (usableWidth <= 0) return;
        panel.style.width = `${Math.round(usableWidth * fraction)}px`;
    };

    // Keep the split ratio (not the raw pixel width) stable across browser
    // resizes, sidebar toggles, and text-size changes, and drop the inline
    // width entirely once the layout collapses to the mobile single column.
    useEffect(() => {
        const panel = panelRef.current;
        if (isMobile) {
            if (panel) panel.style.width = '';
            return;
        }

        applyPanelWidth(fractionRef.current);

        const grid = gridRef.current;
        if (!grid || typeof ResizeObserver === 'undefined') return;
        const observer = new ResizeObserver(() => applyPanelWidth(fractionRef.current));
        observer.observe(grid);
        return () => observer.disconnect();
    }, [isMobile]);

    const commitPendingMove = () => {
        rafRef.current = null;
        const grid = gridRef.current;
        if (!grid || pendingClientXRef.current === null) return;
        const rect = grid.getBoundingClientRect();
        const usableWidth = rect.width - PASSAGE_DIVIDER_WIDTH;
        if (usableWidth <= 0) return;
        const offsetX = pendingClientXRef.current - rect.left;
        const fraction = clamp(offsetX / usableWidth, PASSAGE_PANEL_MIN_FRACTION, PASSAGE_PANEL_MAX_FRACTION);
        fractionRef.current = fraction;
        applyPanelWidth(fraction);
    };

    const handlePointerMove = (event) => {
        const clientX = event.touches ? event.touches[0]?.clientX : event.clientX;
        if (clientX === undefined) return;
        if (event.cancelable) event.preventDefault();
        pendingClientXRef.current = clientX;
        // Coalesce rapid pointer-move events to one DOM write per animation
        // frame instead of one per event, avoiding layout-thrashing jank.
        if (rafRef.current === null) {
            rafRef.current = window.requestAnimationFrame(commitPendingMove);
        }
    };

    const stopDragging = () => {
        setIsDragging(false);
        document.body.classList.remove('is-resizing-panels');
        window.removeEventListener('mousemove', handlePointerMove);
        window.removeEventListener('mouseup', stopDragging);
        window.removeEventListener('touchmove', handlePointerMove);
        window.removeEventListener('touchend', stopDragging);
        if (rafRef.current !== null) {
            window.cancelAnimationFrame(rafRef.current);
            rafRef.current = null;
        }
    };

    const startDragging = (event) => {
        if (isMobile) return;
        event.preventDefault();
        setIsDragging(true);
        document.body.classList.add('is-resizing-panels');
        window.addEventListener('mousemove', handlePointerMove);
        window.addEventListener('mouseup', stopDragging);
        window.addEventListener('touchmove', handlePointerMove, { passive: false });
        window.addEventListener('touchend', stopDragging);
    };

    // Arrow-key resizing for keyboard/screen-reader users operating the
    // divider as a standard ARIA "separator".
    const handleDividerKeyDown = (event) => {
        if (isMobile) return;
        const step = event.shiftKey ? 0.08 : 0.02;
        let next = null;
        if (event.key === 'ArrowLeft') next = fractionRef.current - step;
        if (event.key === 'ArrowRight') next = fractionRef.current + step;
        if (next === null) return;
        event.preventDefault();
        fractionRef.current = clamp(next, PASSAGE_PANEL_MIN_FRACTION, PASSAGE_PANEL_MAX_FRACTION);
        applyPanelWidth(fractionRef.current);
    };

    useEffect(() => () => stopDragging(), []);

    return (
        <div ref={gridRef} className={`passage-step-grid ${isDragging ? 'is-resizing' : ''}`}>
            <aside ref={panelRef} className={collapsed ? 'passage-panel collapsed' : 'passage-panel'}>
                <div className="passage-panel-header">
                    <div>
                        <span className="passage-panel-eyebrow">
                            <i className="bi bi-file-earmark-text-fill"></i>
                            Reading Passage
                        </span>
                        <h2 className="passage-panel-title">{step.passage.title}</h2>
                    </div>
                    <button
                        type="button"
                        className="btn btn-sm btn-soft passage-collapse-toggle"
                        onClick={() => setCollapsed((value) => !value)}
                    >
                        <i className={`bi ${collapsed ? 'bi-chevron-down' : 'bi-chevron-up'}`}></i>
                        {collapsed ? 'Show passage' : 'Hide passage'}
                    </button>
                </div>
                <div className="passage-panel-body">
                    {step.passage.image_url && (
                        <img src={step.passage.image_url} alt={step.passage.title} className="passage-panel-image" />
                    )}
                    <div
                        className="passage-panel-content math-content"
                        dangerouslySetInnerHTML={{ __html: step.passage.content }}
                    />

                </div>
            </aside>

            {!isMobile && (
                <div
                    className="passage-resize-handle"
                    role="separator"
                    aria-orientation="vertical"
                    aria-label="Resize summary and question panels"
                    aria-valuenow={Math.round(fractionRef.current * 100)}
                    aria-valuemin={Math.round(PASSAGE_PANEL_MIN_FRACTION * 100)}
                    aria-valuemax={Math.round(PASSAGE_PANEL_MAX_FRACTION * 100)}
                    tabIndex={0}
                    onMouseDown={startDragging}
                    onTouchStart={startDragging}
                    onKeyDown={handleDividerKeyDown}
                >
                    <span className="passage-resize-grip"></span>
                </div>
            )}

            <div className="passage-questions-column">
                {step.questions.map((question) => (
                    <div className="passage-question-block" key={question.id}>
                        <div className="question-meta">
                            <span>Question {questionNumbers[question.id]}</span>
                            <strong>{question.marks} marks</strong>
                        </div>
                        <div className="exam-question-text math-content passage-preview" dangerouslySetInnerHTML={{ __html: question.text }} />
                        <QuestionOptions question={question} onSelect={onSelect} />
                    </div>
                ))}
            </div>
        </div>
    );
}

function StudentExamApp({ root }) {
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [showSubmitModal, setShowSubmitModal] = useState(false);
    const [payload, setPayload] = useState(null);
    const [activeIndex, setActiveIndex] = useState(0);
    const [remainingSeconds, setRemainingSeconds] = useState(0);
    const [fontStepIndex, setFontStepIndex] = useState(loadStoredFontStep);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    const stateUrl = root.dataset.stateUrl;
    const answerUrl = root.dataset.answerUrl;
    const submitUrl = root.dataset.submitUrl;

    const steps = payload?.items || [];
    const activeStep = steps[activeIndex];

    const allQuestions = useMemo(() => flattenQuestions(steps), [steps]);
    const questionStepIndex = useMemo(() => buildQuestionStepIndex(steps), [steps]);
    const questionNumbers = useMemo(() => {
        const map = {};
        allQuestions.forEach((question, index) => { map[question.id] = index + 1; });
        return map;
    }, [allQuestions]);

    const answeredCount = useMemo(() => allQuestions.filter((question) => question.selected_option_id).length, [allQuestions]);

    // Submit button is only enabled when on the LAST step
    const isOnLastStep = steps.length > 0 && activeIndex === steps.length - 1;

    const loadState = async () => {
        const response = await fetch(stateUrl, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        setPayload(data);
        setLoading(false);
        if (data.attempt.status === 'submitted' && data.attempt.result_url) {
            window.location.href = data.attempt.result_url;
        }
    };

    const submitExam = async () => {
        if (submitting) return;
        setSubmitting(true);
        setShowSubmitModal(false);
        const response = await fetch(submitUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({}),
        });
        const data = await response.json();
        window.location.href = data.result_url;
    };

    useEffect(() => {
        loadState();
    }, []);

    useEffect(() => {
        if (!payload?.attempt?.expires_at) return;
        const expiresAt = new Date(payload.attempt.expires_at).getTime();
        const timer = window.setInterval(() => {
            const seconds = Math.ceil((expiresAt - Date.now()) / 1000);
            setRemainingSeconds(seconds);
            if (seconds <= 0) {
                window.clearInterval(timer);
                submitExam();
            }
        }, 1000);
        return () => window.clearInterval(timer);
    }, [payload?.attempt?.expires_at]);

    useEffect(() => {
        window.MathJax?.typesetPromise?.();
    }, [activeIndex, payload]);

    useEffect(() => {
        try {
            window.localStorage.setItem(FONT_STEP_STORAGE_KEY, String(FONT_STEPS[fontStepIndex]));
        } catch {
            // Storage unavailable (private browsing, etc.) — the choice just
            // won't persist across reloads; the exam itself is unaffected.
        }
    }, [fontStepIndex]);

    const decreaseFontSize = () => setFontStepIndex((index) => Math.max(0, index - 1));
    const increaseFontSize = () => setFontStepIndex((index) => Math.min(FONT_STEPS.length - 1, index + 1));

    const saveAnswer = async (questionId, optionId) => {
        setPayload((current) => ({
            ...current,
            items: updateStepsWithAnswer(current.items, questionId, optionId),
        }));

        await fetch(answerUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ question_id: questionId, option_id: optionId }),
        });
    };

    if (loading) {
        return <div className="exam-runner-shell"><div className="exam-loading">Loading exam...</div></div>;
    }

    if (!activeStep) {
        return <div className="exam-runner-shell"><div className="exam-loading">No questions available.</div></div>;
    }

    return (
        <div className="exam-runner-shell" style={{ '--exam-font-scale': FONT_STEPS[fontStepIndex] }}>
            <header className="exam-runner-header">
                <div>
                    <span>Online Exam</span>
                    <h1>{payload.exam.title}</h1>
                </div>
                <div className={remainingSeconds <= 60 ? 'exam-timer danger' : 'exam-timer'}>
                    <i className="bi bi-clock-fill"></i>
                    {formatTime(remainingSeconds)}
                </div>
            </header>

            <div className="exam-progress">
                <div style={{ width: `${(answeredCount / allQuestions.length) * 100}%` }}></div>
            </div>

            <div className="exam-toolbar">
                <div className="exam-nav-wrap">
                    <div className="exam-nav-label">
                        <span>Question Navigation</span>
                        <span>{answeredCount}/{allQuestions.length} answered</span>
                    </div>
                    <div className="exam-nav-scroll">
                        {allQuestions.map((question, index) => (
                            <button
                                type="button"
                                key={question.id}
                                className={`exam-nav-btn ${questionStepIndex[question.id] === activeIndex ? 'active' : ''} ${question.selected_option_id ? 'answered' : ''}`}
                                onClick={() => setActiveIndex(questionStepIndex[question.id])}
                            >
                                {index + 1}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="exam-font-control" aria-label="Question text size">
                    <span className="exam-font-control-label">
                        <i className="bi bi-fonts"></i>
                        Text Size
                    </span>
                    <button type="button" onClick={decreaseFontSize} disabled={fontStepIndex === 0} aria-label="Decrease text size">
                        <i className="bi bi-dash-lg"></i>
                    </button>
                    <button type="button" onClick={increaseFontSize} disabled={fontStepIndex === FONT_STEPS.length - 1} aria-label="Increase text size">
                        <i className="bi bi-plus-lg"></i>
                    </button>
                </div>
            </div>

            <main className="exam-question-panel">
                {activeStep.type === 'question' ? (
                    <>
                        <div className="question-meta">
                            <span>Question {questionNumbers[activeStep.question.id]} of {allQuestions.length}</span>
                            <strong>{activeStep.question.marks} marks</strong>
                        </div>
                        <div className="exam-question-text math-content">{activeStep.question.text}</div>
                        <QuestionOptions question={activeStep.question} onSelect={saveAnswer} />
                    </>
                ) : (
                    <PassageStep step={activeStep} questionNumbers={questionNumbers} onSelect={saveAnswer} />
                )}

                <div className="exam-controls">
                    <button type="button" className="btn btn-soft" onClick={() => setActiveIndex(Math.max(0, activeIndex - 1))} disabled={activeIndex === 0}>
                        <i className="bi bi-arrow-left"></i>
                        Previous
                    </button>
                    <button type="button" className="btn btn-soft" onClick={() => setActiveIndex(Math.min(steps.length - 1, activeIndex + 1))} disabled={activeIndex === steps.length - 1}>
                        Next
                        <i className="bi bi-arrow-right"></i>
                    </button>
                    <button
                        type="button"
                        className="btn btn-primary"
                        disabled={!isOnLastStep || submitting}
                        title={!isOnLastStep ? 'Submit is available on the last question' : 'Submit your exam'}
                        onClick={() => setShowSubmitModal(true)}
                    >
                        <i className="bi bi-send-fill"></i>
                        Submit Exam
                    </button>
                </div>
            </main>

            {showSubmitModal && (
                <div className="modal fade show d-block" tabIndex="-1" role="dialog" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
                    <div className="modal-dialog modal-dialog-centered" role="document">
                        <div className="modal-content confirm-modal">
                            <div className="modal-header">
                                <div>
                                    <span className="page-kicker">Final Step</span>
                                    <h2 className="modal-title fs-5">Submit Exam</h2>
                                </div>
                                <button type="button" className="btn-close" onClick={() => setShowSubmitModal(false)} aria-label="Close"></button>
                            </div>
                            <div className="modal-body">
                                <div className="begin-exam-icon">
                                    <i className="bi bi-send-check-fill"></i>
                                </div>
                                <p className="mb-0 text-center">Are you sure you want to submit the exam? You cannot change your answers after submission.</p>
                            </div>
                            <div className="modal-footer">
                                <button type="button" className="btn btn-outline-secondary" onClick={() => setShowSubmitModal(false)}>Cancel</button>
                                <button type="button" className="btn btn-primary" onClick={submitExam} disabled={submitting}>
                                    {submitting ? <span className="spinner-border spinner-border-sm me-2"></span> : <i className="bi bi-send-fill"></i>}
                                    Submit Exam
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

// Anti-copy protection for the exam runner. Scoped entirely to this page
// load (student-exam-root, and therefore .exam-runner-shell, only exists on
// the exam attempt page), so the dashboard, results, admin, super admin and
// branch pages are never touched. MCQ options remain buttons with onClick
// handlers, so none of this affects click/tap selection, scrolling, the
// timer, navigation or submission — only text selection/copy is blocked.
function enableExamCopyProtection() {
    const isInsideExam = (target) => !!target?.closest?.('.exam-runner-shell');

    document.addEventListener('contextmenu', (event) => {
        event.preventDefault();
    });

    document.addEventListener('copy', (event) => {
        if (isInsideExam(event.target)) event.preventDefault();
    });

    document.addEventListener('cut', (event) => {
        if (isInsideExam(event.target)) event.preventDefault();
    });

    document.addEventListener('selectstart', (event) => {
        if (isInsideExam(event.target)) event.preventDefault();
    });

    document.addEventListener('dragstart', (event) => {
        if (isInsideExam(event.target)) event.preventDefault();
    });

    // Mobile long-press "select/copy" callouts are a native gesture, not a
    // JS event we can intercept directly — the CSS -webkit-touch-callout:
    // none and user-select: none rules on .exam-runner-shell (admin.css)
    // are what suppress them on iOS/Android.

    document.addEventListener('keydown', (event) => {
        if (!isInsideExam(event.target)) return;
        const key = event.key.toLowerCase();
        // Copy/cut, and select-all (the usual precursor to a copy).
        if ((event.ctrlKey || event.metaKey) && ['c', 'x', 'a'].includes(key)) {
            event.preventDefault();
        }
    });
}

const root = document.getElementById('student-exam-root');
if (root) {
    window.MathJax = { tex: { inlineMath: [['$', '$'], ['\\(', '\\)']] } };
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js';
    script.async = true;
    document.head.appendChild(script);
    createRoot(root).render(<StudentExamApp root={root} />);
    enableExamCopyProtection();
}
