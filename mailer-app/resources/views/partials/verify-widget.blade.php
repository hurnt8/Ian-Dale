{{--
    Panneau de résultats de vérification, relié à un <textarea>.
    Variables : $textarea (id du textarea), $button (id du bouton "Vérifier")
--}}
<div id="verify-panel" class="card mt-3 d-none">
    <div class="card-header flex-wrap">
        <span><i class="bi bi-patch-check me-2"></i>Résultat de la vérification</span>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-sm btn-light" id="verify-fix" title="Remplacer les fautes de frappe par la correction proposée"><i class="bi bi-magic me-1"></i> Corriger les fautes</button>
            <button type="button" class="btn btn-sm btn-light" id="verify-keep" title="Ne garder dans la liste que les adresses envoyables"><i class="bi bi-funnel me-1"></i> Garder les valides</button>
        </div>
    </div>
    <div class="card-body border-bottom py-3">
        <div class="d-flex flex-wrap gap-3 align-items-center mb-2" id="verify-headline"></div>
        <div class="verify-summary" id="verify-summary"></div>
    </div>
    <div class="table-responsive" style="max-height: 460px">
        <table class="table table-hover verify-table">
            <thead><tr><th>Adresse</th><th>Résultat</th><th>Détail</th></tr></thead>
            <tbody id="verify-rows"></tbody>
        </table>
    </div>
</div>

<script>
(() => {
    const area = document.getElementById(@json($textarea));
    const btn = document.getElementById(@json($button));
    const panel = document.getElementById('verify-panel');
    const rowsEl = document.getElementById('verify-rows');
    const summaryEl = document.getElementById('verify-summary');
    const headEl = document.getElementById('verify-headline');
    const token = document.querySelector('meta[name="csrf-token"]').content;
    let data = null, filter = null;

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const pill = (label, color) => `<span class="pill" style="color:${color};background:${color}14">${esc(label)}</span>`;

    function render() {
        const rows = data.results.filter(r => !filter || r.status === filter);
        rowsEl.innerHTML = rows.length ? rows.map(r => `
            <tr>
                <td class="email-cell">${esc(r.email)}</td>
                <td>${pill(r.label, r.color)}${r.suppressed === 'unsubscribed' ? ' ' + pill('Désinscrit', '#6b7280') : ''}</td>
                <td class="small text-secondary">${esc(r.detail)}
                    ${r.suggestion ? `<button type="button" class="suggest ms-1" data-from="${esc(r.email)}" data-to="${esc(r.suggestion)}">Corriger</button>` : ''}
                </td>
            </tr>`).join('') : '<tr><td colspan="3" class="empty">Aucune adresse dans cette catégorie.</td></tr>';

        const groups = {};
        data.results.forEach(r => { groups[r.status] ??= { label: r.label, n: 0 }; groups[r.status].n++; });
        summaryEl.innerHTML = `<span class="tab ${!filter ? 'active' : ''}" data-f="">Toutes<span class="n">${data.total}</span></span>`
            + Object.entries(groups).map(([k, g]) => `<span class="tab ${filter === k ? 'active' : ''}" data-f="${k}">${esc(g.label)}<span class="n">${g.n}</span></span>`).join('');

        const bad = data.total - data.sendable;
        headEl.innerHTML = `
            <span class="fw-semibold text-success"><i class="bi bi-check-circle me-1"></i>${data.sendable} envoyable(s)</span>
            ${bad ? `<span class="fw-semibold text-danger"><i class="bi bi-x-circle me-1"></i>${bad} à écarter</span>` : ''}
            ${data.duplicates ? `<span class="text-secondary"><i class="bi bi-files me-1"></i>${data.duplicates} doublon(s)</span>` : ''}`;
    }

    function replaceInArea(from, to) {
        const re = new RegExp(from.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
        area.value = area.value.replace(re, to);
        area.dispatchEvent(new Event('input'));
    }

    btn.addEventListener('click', async () => {
        if (!area.value.trim()) { area.focus(); return; }
        const label = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Vérification…';
        try {
            const res = await fetch(@json(route('verification.check')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ emails: area.value }),
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.message || 'Erreur de vérification');
            data = json; filter = null;
            panel.classList.remove('d-none');
            render();
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (e) {
            alert(e.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = label;
        }
    });

    summaryEl.addEventListener('click', e => {
        const t = e.target.closest('[data-f]');
        if (t) { filter = t.dataset.f || null; render(); }
    });

    rowsEl.addEventListener('click', e => {
        const b = e.target.closest('.suggest');
        if (!b) return;
        replaceInArea(b.dataset.from, b.dataset.to);
        const r = data.results.find(x => x.email === b.dataset.from);
        Object.assign(r, { email: b.dataset.to, suggestion: null, label: 'Corrigée', status: 'fixed', color: '#047857', blocking: false, detail: 'Correction appliquée — revérifiez pour confirmer.' });
        render();
    });

    document.getElementById('verify-fix').addEventListener('click', () => {
        data?.results.filter(r => r.suggestion).forEach(r => {
            replaceInArea(r.email, r.suggestion);
            Object.assign(r, { email: r.suggestion, suggestion: null, label: 'Corrigée', status: 'fixed', color: '#047857', blocking: false, detail: 'Correction appliquée — revérifiez pour confirmer.' });
        });
        data && render();
    });

    document.getElementById('verify-keep').addEventListener('click', async () => {
        if (!data) return;
        const keep = data.results.filter(r => !r.blocking && !r.suppressed).map(r => r.email);
        const ok = await askConfirm({
            title: 'Garder uniquement les adresses valides ?',
            message: `La liste sera remplacée par les ${keep.length} adresse(s) envoyable(s). ${data.total - keep.length} adresse(s) seront retirées.`,
            button: 'Remplacer la liste', variant: 'warning',
        });
        if (!ok) return;
        area.value = keep.join(', ');
        area.dispatchEvent(new Event('input'));
        data.results = data.results.filter(r => !r.blocking && !r.suppressed);
        data.total = data.sendable = keep.length;
        data.duplicates = 0;
        render();
    });
})();
</script>
