{{--
    Modal de confirmation réutilisable.

    Sur un formulaire :
      <form data-confirm="Message" data-confirm-title="Titre" data-confirm-button="Supprimer" data-confirm-variant="danger">
    En JavaScript :
      if (await askConfirm({ title, message, button, variant })) { ... }
--}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content confirm-modal">
            <div class="modal-body text-center p-4">
                <div class="confirm-icon mx-auto mb-3" id="confirmModalIcon"><i class="bi bi-question-lg"></i></div>
                <h2 class="h5 fw-bold mb-2" id="confirmModalTitle">Confirmer</h2>
                <p class="text-secondary mb-0" id="confirmModalMessage"></p>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4 d-flex gap-2">
                <button type="button" class="btn btn-light flex-fill" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn flex-fill" id="confirmModalOk">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const el = document.getElementById('confirmModal');
    const modal = new bootstrap.Modal(el);
    const ok = document.getElementById('confirmModalOk');
    const icon = document.getElementById('confirmModalIcon');
    const icons = { danger: 'bi-exclamation-triangle', success: 'bi-send-check', warning: 'bi-arrow-repeat', primary: 'bi-question-lg' };
    let resolver = null;

    window.askConfirm = ({ title = 'Confirmer', message = '', button = 'Confirmer', variant = 'primary' } = {}) => {
        document.getElementById('confirmModalTitle').textContent = title;
        document.getElementById('confirmModalMessage').textContent = message;
        ok.textContent = button;
        ok.className = `btn btn-${variant} flex-fill`;
        icon.className = `confirm-icon mx-auto mb-3 tone-${{ danger: 'red', success: 'green', warning: 'amber' }[variant] ?? 'indigo'}`;
        icon.innerHTML = `<i class="bi ${icons[variant] ?? icons.primary}"></i>`;
        modal.show();
        return new Promise(resolve => { resolver = resolve; });
    };

    ok.addEventListener('click', () => { const r = resolver; resolver = null; modal.hide(); r?.(true); });
    el.addEventListener('hidden.bs.modal', () => { resolver?.(false); resolver = null; });
    el.addEventListener('shown.bs.modal', () => ok.focus());

    // Interception des formulaires marqués data-confirm
    document.addEventListener('submit', async e => {
        const form = e.target;
        if (!form.matches('form[data-confirm]') || form.dataset.confirmed === '1') return;
        e.preventDefault();
        const d = form.dataset;
        const message = typeof window[d.confirmMessageFn] === 'function' ? window[d.confirmMessageFn](form) : d.confirm;
        if (await askConfirm({ title: d.confirmTitle, message, button: d.confirmButton, variant: d.confirmVariant })) {
            form.dataset.confirmed = '1';
            form.requestSubmit ? form.requestSubmit(e.submitter) : form.submit();
        }
    });
})();
</script>
