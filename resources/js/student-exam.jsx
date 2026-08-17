import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';

function formatTime(seconds) {
    const value = Math.max(0, seconds);
    const minutes = Math.floor(value / 60).toString().padStart(2, '0');
    const remainingSeconds = Math.floor(value % 60).toString().padStart(2, '0');
    return `${minutes}:${remainingSeconds}`;
}

function StudentExamApp({ root }) {
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [showSubmitModal, setShowSubmitModal] = useState(false);
    const [payload, setPayload] = useState(null);
    const [activeIndex, setActiveIndex] = useState(0);
    const [remainingSeconds, setRemainingSeconds] = useState(0);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    const stateUrl = root.dataset.stateUrl;
    const answerUrl = root.dataset.answerUrl;
    const submitUrl = root.dataset.submitUrl;

    const activeQuestion = payload?.questions?.[activeIndex];
    const answeredCount = useMemo(() => payload?.questions?.filter((question) => question.selected_option_id).length || 0, [payload]);

    // Submit button is only enabled when on the LAST question
    const isOnLastQuestion = payload?.questions && activeIndex === payload.questions.length - 1;

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

    const saveAnswer = async (questionId, optionId) => {
        setPayload((current) => ({
            ...current,
            questions: current.questions.map((question) => (
                question.id === questionId ? { ...question, selected_option_id: optionId } : question
            )),
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

    if (!activeQuestion) {
        return <div className="exam-runner-shell"><div className="exam-loading">No questions available.</div></div>;
    }

    return (
        <div className="exam-runner-shell">
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
                <div style={{ width: `${(answeredCount / payload.questions.length) * 100}%` }}></div>
            </div>

            <div className="exam-runner-grid">
                <main className="exam-question-panel">
                    <div className="question-meta">
                        <span>Question {activeIndex + 1} of {payload.questions.length}</span>
                        <strong>{activeQuestion.marks} marks</strong>
                    </div>
                    <div className="exam-question-text math-content">{activeQuestion.text}</div>

                    <div className="exam-options">
                        {activeQuestion.options.map((option) => (
                            <button
                                type="button"
                                key={option.id}
                                className={activeQuestion.selected_option_id === option.id ? 'exam-option selected' : 'exam-option'}
                                onClick={() => saveAnswer(activeQuestion.id, option.id)}
                            >
                                <span></span>
                                <div className="math-content">{option.text}</div>
                            </button>
                        ))}
                    </div>

                    <div className="exam-controls">
                        <button type="button" className="btn btn-soft" onClick={() => setActiveIndex(Math.max(0, activeIndex - 1))} disabled={activeIndex === 0}>
                            <i className="bi bi-arrow-left"></i>
                            Previous
                        </button>
                        <button type="button" className="btn btn-soft" onClick={() => setActiveIndex(Math.min(payload.questions.length - 1, activeIndex + 1))} disabled={activeIndex === payload.questions.length - 1}>
                            Next
                            <i className="bi bi-arrow-right"></i>
                        </button>
                        <button
                            type="button"
                            className="btn btn-primary"
                            disabled={!isOnLastQuestion || submitting}
                            title={!isOnLastQuestion ? 'Submit is available on the last question' : 'Submit your exam'}
                            onClick={() => setShowSubmitModal(true)}
                        >
                            <i className="bi bi-send-fill"></i>
                            Submit Exam
                        </button>
                    </div>
                </main>

                <aside className="question-palette">
                    <div>
                        <strong>Question Palette</strong>
                        <span>{answeredCount}/{payload.questions.length} answered</span>
                    </div>
                    <div className="palette-grid">
                        {payload.questions.map((question, index) => (
                            <button
                                type="button"
                                key={question.id}
                                className={`${index === activeIndex ? 'active' : ''} ${question.selected_option_id ? 'answered' : ''}`}
                                onClick={() => setActiveIndex(index)}
                            >
                                {index + 1}
                            </button>
                        ))}
                    </div>
                </aside>
            </div>

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
                                <p className="mb-0 text-center">Are you sure you want to submit your exam? You will not be able to change your answers after submission.</p>
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

const root = document.getElementById('student-exam-root');
if (root) {
    window.MathJax = { tex: { inlineMath: [['$', '$'], ['\\(', '\\)']] } };
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js';
    script.async = true;
    document.head.appendChild(script);
    createRoot(root).render(<StudentExamApp root={root} />);
}