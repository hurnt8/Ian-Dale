<?php

namespace App\Support;

use App\Models\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export CSV des réponses d'une vue (partagé entre l'admin et les agents).
 */
final class PageResponsesExport
{
    public static function download(Page $page): StreamedResponse
    {
        $fields = $page->form['fields'] ?? [];
        $headers = ['Date', ...array_column($fields, 'label')];

        return response()->streamDownload(function () use ($page, $fields, $headers) {
            $out = fopen('php://output', 'wb');
            fprintf($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel
            fputcsv($out, $headers, ';');

            $page->responses()->latest()->chunk(200, function ($chunk) use ($out, $fields) {
                foreach ($chunk as $resp) {
                    $byKey = collect($resp->answers)->keyBy('key');
                    $row = [$resp->created_at->format('d/m/Y H:i')];
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
