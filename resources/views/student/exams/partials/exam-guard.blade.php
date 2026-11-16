{{--
    Exam lock-down for the student exam screen (student/exams/attempt).

    While the attempt is in progress:
    - the sidebar is collapsed and its expand / mobile-menu buttons are hidden;
    - clicking any link outside the exam (sidebar, logo, profile, logout) or
      pressing Back opens a pop-up: leaving submits the exam;
    - reloading or closing the tab shows the browser's "Leave site?" prompt,
      and if the student leaves anyway the exam is submitted;
    - moving the mouse out of the page shows a reminder pop-up;
    - switching to another tab or window (more than 3 seconds away) is
      detected. The first time a warning is shown; the next time the exam is
      submitted immediately. Browsers do not allow a page to block tab
      switching outright, so detect-and-submit is the strongest option.

    Plain JS on purpose: it works without rebuilding the React exam bundle.
    It notices the React app's own submit (Submit button or timer running out)
    by watching its requests to the submit/state URLs, and then stands down.
--}}
<style>
    body.exam-in-progress [data-sidebar-toggle],
    body.exam-in-progress [data-bs-target="#mobileSidebar"] { display: none !important; }
    .exam-guard-icon { width: 56px; height: 56px; border-radius: 50%; display: grid; place-items: center; margin: 0 auto 12px; font-size: 1.6rem; }
    .exam-guard-icon.warn { background: #fef3c7; color: #b45309; }
    .exam-guard-icon.danger { background: #fee2e2; color: #b91c1c; }
</style>

<div class="modal fade" id="examLeaveModal" tabindex="-1" aria-labelledby="examLeaveModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content confirm-modal">
            <div class="modal-header">
                <div>
                    <span class="page-kicker">Exam in progress</span>
                    <h2 class="modal-title fs-5" id="examLeaveModalLabel">Leave the exam?</h2>
                </div>
            </div>
            <div class="modal-body text-center">
                <div class="exam-guard-icon danger"><i class="bi bi-exclamation-octagon-fill"></i></div>
                <p class="mb-0">If you leave this page, <strong>your exam will end and be submitted now</strong> with the answers you have given so far. You cannot come back to it.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-exam-guard-stay>Stay in the exam</button>
                <button type="button" class="btn btn-outline-danger" data-exam-guard-leave>Submit and leave</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="examMouseLeaveModal" tabindex="-1" aria-labelledby="examMouseLeaveModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content confirm-modal">
            <div class="modal-header">
                <div>
                    <span class="page-kicker">Stay on the exam</span>
                    <h2 class="modal-title fs-5" id="examMouseLeaveModalLabel">Your mouse left the exam screen</h2>
                </div>
            </div>
            <div class="modal-body text-center">
                <div class="exam-guard-icon warn"><i class="bi bi-cursor-fill"></i></div>
                <p class="mb-0">Please keep your mouse on the exam. <strong>Opening another tab or window during the exam
                    will submit your exam</strong> after one warning.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Back to my exam</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="examTabWarningModal" tabindex="-1" aria-labelledby="examTabWarningModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content confirm-modal">
            <div class="modal-header">
                <div>
                    <span class="page-kicker">Warning</span>
                    <h2 class="modal-title fs-5" id="examTabWarningModalLabel">You left the exam screen</h2>
                </div>
            </div>
            <div class="modal-body text-center">
                <div class="exam-guard-icon warn"><i class="bi bi-eye-slash-fill"></i></div>
                <p class="mb-0">Switching to another tab or window is not allowed during the exam.
                    <strong>If you leave the exam screen again, your exam will be submitted automatically.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Back to my exam</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="examSubmittedModal" tabindex="-1" aria-labelledby="examSubmittedModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content confirm-modal">
            <div class="modal-header">
                <div>
                    <span class="page-kicker">Exam ended</span>
                    <h2 class="modal-title fs-5" id="examSubmittedModalLabel">Your exam was submitted</h2>
                </div>
            </div>
            <div class="modal-body text-center">
                <div class="exam-guard-icon danger"><i class="bi bi-send-check-fill"></i></div>
                <p class="mb-0">You left the exam screen again, so your exam has been submitted with the answers you gave.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-exam-guard-result>See my result</button>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const root = document.getElementById('student-exam-root');
        if (!root) return;

        const submitUrl = root.dataset.submitUrl;
        const stateUrl = root.dataset.stateUrl;
        const dashboardUrl = @json(route('student.dashboard'));
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        const ALLOWED_SWITCHES = 1;      // warnings before the next switch submits
        const AWAY_GRACE_MS = 3000;      // window blur shorter than this is ignored

        let active = true;               // false once the attempt is submitted (by anyone)
        let switches = 0;
        let away = false;
        let blurTimer = null;
        let pending = null;              // { href } or { form } waiting on the leave pop-up
        let submitPromise = null;
        let autoSubmitted = false;

        const modal = (id) => bootstrap.Modal.getOrCreateInstance(document.getElementById(id));

        // ---- lock the layout -------------------------------------------------
        document.body.classList.add('sidebar-collapsed', 'exam-in-progress');

        // ---- notice the React app's own submit / an already-submitted attempt ----
        const nativeFetch = window.fetch.bind(window);
        const urlOf = (input) => (typeof input === 'string' ? input : input?.url ?? '');
        window.fetch = async (input, init) => {
            const url = urlOf(input);
            if (url === submitUrl) active = false;
            const response = await nativeFetch(input, init);
            if (url === stateUrl) {
                response.clone().json().then((data) => {
                    if (data?.attempt?.status === 'submitted') active = false;
                }).catch(() => {});
            }
            return response;
        };

        const submitNow = () => {
            if (submitPromise) return submitPromise;
            active = false;
            submitPromise = nativeFetch(submitUrl, {
                method: 'POST',
                keepalive: true,
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({}),
            }).then((response) => response.json()).then((data) => data?.result_url ?? dashboardUrl)
              .catch(() => dashboardUrl);
            return submitPromise;
        };

        // ---- links, logout, Back ------------------------------------------------
        const askToLeave = (target) => {
            pending = target;
            modal('examLeaveModal').show();
        };

        document.addEventListener('click', (event) => {
            if (!active || root.contains(event.target)) return;

            const logoutButton = event.target.closest('[data-logout-form] button, [data-logout-form] [type="submit"]');
            if (logoutButton) {
                event.preventDefault();
                event.stopImmediatePropagation();
                askToLeave({ form: logoutButton.closest('form') });
                return;
            }

            const link = event.target.closest('a[href]');
            const href = link?.getAttribute('href') ?? '';
            if (!link || href.startsWith('#') || href.startsWith('javascript:') || link.closest('.modal')) return;

            event.preventDefault();
            event.stopImmediatePropagation();
            askToLeave({ href: link.href });
        }, true);

        // ---- block every way a page can open a NEW tab/window ---------------------
        // Ctrl/Shift/Cmd+click and middle-click on links, right-click "Open in
        // new tab", dragging a link to the tab bar, and Ctrl/Shift+Enter on a
        // focused link. (The browser's own Ctrl+T / Ctrl+N cannot be blocked by
        // any web page; that new tab hides this one, which the tab-switch rule
        // below catches.)
        const newTabLink = (event) => event.target.closest?.('a[href], area[href]');

        document.addEventListener('click', (event) => {
            if (!active) return;
            const link = newTabLink(event);
            if (link && (event.ctrlKey || event.metaKey || event.shiftKey || link.target === '_blank')) {
                event.preventDefault();
                event.stopImmediatePropagation();
                if (!root.contains(link)) askToLeave({ href: link.href });
            }
        }, true);

        ['auxclick', 'mousedown'].forEach((type) => document.addEventListener(type, (event) => {
            if (active && event.button === 1) {      // middle button
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        }, true));

        document.addEventListener('contextmenu', (event) => {
            if (active) event.preventDefault();      // no "Open link in new tab"
        }, true);

        document.addEventListener('dragstart', (event) => {
            if (active) event.preventDefault();      // no dragging links/images to the tab bar
        }, true);

        document.addEventListener('keydown', (event) => {
            if (!active || event.key !== 'Enter' || !(event.ctrlKey || event.metaKey || event.shiftKey)) return;
            if (newTabLink(event)) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        }, true);

        // Back button: keep an extra history entry so Back lands here first.
        // In-page "#..." links also fire popstate; only a real Back (which
        // returns to the page's original address) asks to leave.
        const initialHash = location.hash;
        history.pushState({ examGuard: true }, '', location.href);
        window.addEventListener('popstate', () => {
            if (!active || location.hash !== initialHash) return;
            history.pushState({ examGuard: true }, '', location.href);
            askToLeave({ href: dashboardUrl });
        });

        document.querySelector('[data-exam-guard-stay]').addEventListener('click', () => {
            pending = null;
            modal('examLeaveModal').hide();
        });

        document.querySelector('[data-exam-guard-leave]').addEventListener('click', async (event) => {
            event.currentTarget.disabled = true;
            const target = pending;
            await submitNow();
            if (target?.form) {
                target.form.submit();              // programmatic submit skips the logout pop-up
            } else {
                window.location.href = target?.href ?? dashboardUrl;
            }
        });

        // ---- reload / close / typing a new address -------------------------------
        window.addEventListener('beforeunload', (event) => {
            if (!active) return;
            event.preventDefault();
            event.returnValue = '';
        });
        window.addEventListener('pagehide', () => {
            if (active) submitNow();               // they chose "Leave" on the browser prompt
        });

        // ---- switching tabs / windows ---------------------------------------------
        const leftScreen = () => {
            if (!active || away) return;
            away = true;
            if (switches >= ALLOWED_SWITCHES) {
                autoSubmitted = true;
                submitNow();                       // submit the moment they leave again
            }
        };

        const cameBack = () => {
            if (!away) return;
            away = false;
            // Close the mouse reminder first so two pop-ups never stack.
            bootstrap.Modal.getInstance(document.getElementById('examMouseLeaveModal'))?.hide();
            if (autoSubmitted) {
                bootstrap.Modal.getInstance(document.getElementById('examLeaveModal'))?.hide();
                modal('examSubmittedModal').show();
                return;
            }
            if (!active) return;
            switches += 1;
            modal('examTabWarningModal').show();
        };

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                clearTimeout(blurTimer);
                leftScreen();
            } else if (document.hasFocus()) {
                cameBack();
            }
        });
        window.addEventListener('blur', () => {
            clearTimeout(blurTimer);
            blurTimer = setTimeout(leftScreen, AWAY_GRACE_MS);
        });
        window.addEventListener('focus', () => {
            clearTimeout(blurTimer);
            if (!document.hidden) cameBack();
        });

        // ---- mouse leaves the exam page ------------------------------------------
        // Moving the pointer out of the page (towards the tab bar, address bar
        // or another window) shows a reminder pop-up. It does not count as a
        // violation by itself; actually switching tab/window does (see above).
        const anyModalOpen = () => document.querySelector('.modal.show') !== null;
        document.documentElement.addEventListener('mouseleave', (event) => {
            if (!active || anyModalOpen()) return;
            if (event.relatedTarget !== null) return;   // moved onto another element, still inside
            modal('examMouseLeaveModal').show();
        });

        document.querySelector('[data-exam-guard-result]').addEventListener('click', async () => {
            window.location.href = await submitNow();
        });
    })();
</script>
