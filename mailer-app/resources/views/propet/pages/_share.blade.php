{{-- Lien personnel d'un agent pour une vue. Variable : $url --}}
<div class="input-group input-group-sm share-link">
    <input type="text" class="form-control email-cell" value="{{ $url }}" readonly onclick="this.select()" aria-label="Mon lien personnel">
    <button type="button" class="btn btn-primary" onclick="(b => { const i = b.previousElementSibling; i.select(); navigator.clipboard?.writeText(i.value); b.innerHTML = '<i class=\'bi bi-check2\'></i> Copié'; setTimeout(() => b.innerHTML = '<i class=\'bi bi-clipboard\'></i> Copier', 1800); })(this)">
        <i class="bi bi-clipboard"></i> Copier
    </button>
</div>
