/* Hostel Allocation Portal — main.js */
'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ── Auto-uppercase matric number field ──────────────────────────────────
    document.querySelectorAll('input[name="matric"]').forEach(el => {
        el.addEventListener('input', () => {
            const pos = el.selectionStart;
            el.value = el.value.toUpperCase();
            el.setSelectionRange(pos, pos);
        });
    });

    // ── Prevent double-submit on all forms ─────────────────────────────────
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function (e) {
            const btn = this.querySelector('button[type="submit"], input[type="submit"]');
            if (!btn) return;

            // Already submitted guard
            if (btn.dataset.submitted === '1') {
                e.preventDefault();
                return;
            }

            btn.dataset.submitted = '1';
            const originalText = btn.textContent.trim();
            btn.disabled = true;
            btn.innerHTML = '<span class="loading-spinner"></span> Processing…';

            // Re-enable after 15s as a safety net
            setTimeout(() => {
                btn.disabled = false;
                btn.dataset.submitted = '0';
                btn.textContent = originalText;
            }, 15_000);
        });
    });

    // ── Password strength indicator ─────────────────────────────────────────
    const pwdInput = document.getElementById('password');
    const pwdHint  = document.getElementById('password-hint');
    if (pwdInput && pwdHint) {
        pwdInput.addEventListener('input', () => {
            const val = pwdInput.value;
            let strength = 0;
            if (val.length >= 8)                    strength++;
            if (/[A-Z]/.test(val))                  strength++;
            if (/[0-9]/.test(val))                  strength++;
            if (/[^A-Za-z0-9]/.test(val))           strength++;

            const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
            const colors = ['', '#e53e3e', '#dd6b20', '#d69e2e', '#1a7f4b'];
            pwdHint.textContent = val.length === 0 ? '' : `Password strength: ${labels[strength]}`;
            pwdHint.style.color = colors[strength] || '';
        });
    }

    // ── Auto-dismiss flash messages ─────────────────────────────────────────
    document.querySelectorAll('.alert').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity .5s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        }, 6_000);
    });

    // ── Confirm dangerous admin actions ────────────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

});
