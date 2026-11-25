@php
    $fields = $page->form['fields'] ?? [];
    // Vue partagée admin / agent : les liens changent selon l'espace.
    $backUrl ??= route('admin.pages.index');
    $exportUrl ??= route('admin.pages.responses.export', $page);
@endphp
@extends('layouts.app')
@section('title', 'Réponses — '.$page->title())
@section('subtitle')
    <a href="{{ $backUrl }}" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Vues</a>
@endsection
@section('actions')
    @if($page->is_published)
        <a href="{{ route('page.show', $page->slug) }}" target="_blank" class="btn btn-light"><i class="bi bi-box-arrow-up-right me-1"></i> Ouvrir la vue</a>
    @endif
    @if($responses->total())
        <a href="{{ $exportUrl }}" class="btn btn-primary"><i class="bi bi-download me-1"></i> Exporter (CSV)</a>
    @endif
@endsection

@section('content')
<div class="card">
    <div class="card-header"><span><i class="bi bi-chat-left-quote me-2"></i>{{ $responses->total() }} réponse(s)</span></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr>
                <th>Date</th>
                @foreach($fields as $f)<th>{{ $f['label'] }}</th>@endforeach
            </tr></thead>
            <tbody>
            @forelse($responses as $resp)
                @php $byKey = collect($resp->answers)->keyBy('key'); @endphp
                <tr>
                    <td class="text-secondary small text-nowrap">{{ $resp->created_at->format('d/m/Y H:i') }}</td>
                    @foreach($fields as $f)
                        <td>{{ $byKey->has($f['key']) ? \App\Support\ResponseForm::display($byKey[$f['key']]) : '—' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($fields) + 1 }}" class="empty"><i class="bi bi-inbox"></i>Aucune réponse pour l'instant.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($responses->hasPages())<div class="mt-3">{{ $responses->links() }}</div>@endif
@endsection
