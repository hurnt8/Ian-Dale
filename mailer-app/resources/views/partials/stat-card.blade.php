{{-- $label, $value, $icon, $tone --}}
<div class="card h-100">
    <div class="stat-card">
        <div class="stat-icon tone-{{ $tone }}"><i class="bi {{ $icon }}"></i></div>
        <div>
            <div class="stat-label">{{ $label }}</div>
            <div class="stat-value">{{ number_format($value, 0, ',', ' ') }}</div>
        </div>
    </div>
</div>
