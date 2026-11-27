<?php

namespace App\Support;

use App\Models\Page;
use App\Models\PageResponse;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export CSV des réponses d'une vue (partagé entre l'admin et les agents).
 * $scope limite les réponses exportées (ex. celles visibles par l'agent connecté).
 */
final class PageResponsesExport
{
    /** @param Builder<PageResponse>|null $scope */
    public static function download(Page $page, ?Builder $scope = null): StreamedResponse
    {
        $fields = $page->form['fields'] ?? [];
        $headers = ['Date', 'Agent', ...array_column($fields, 'label')];
        $query = ($scope ?? PageResponse::query())->where('page_responses.page_id', $page->id)->with('agent:id,name');

        return response()->streamDownload(function () use ($query, $fields, $headers) {
            $out = fopen('php://output', 'wb');
            fprintf($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel
            fputcsv($out, $headers, ';');

            $query->orderByDesc('page_responses.id')->chunk(200, function ($chunk) use ($out, $fields) {
                foreach ($chunk as $resp) {
                    $byKey = collect($resp->answers)->keyBy('key');
                    $row = [$resp->created_at->format('d/m/Y H:i'), $resp->agent?->name ?? 'Lien général'];
                    foreach ($fields as $f) {
                        $row[] = $byKey->has($f['key']) ? ResponseForm::display($byKey[$f['key']]) : '';
                    }
                    fputcsv($out, $row, ';');
                }
            });
            fclose($out);
        }, 'reponses-'.$page->slug.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
