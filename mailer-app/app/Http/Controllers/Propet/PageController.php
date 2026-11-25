<?php

namespace App\Http\Controllers\Propet;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\PageResponsesExport;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Vues attribuées à l'agent connecté : il en consulte et exporte les réponses (lecture seule).
 */
class PageController extends Controller
{
    public function index(Request $request): View
    {
        return view('propet.pages.index', [
            'pages' => $request->user()->assignedPages()->withCount('responses')->latest('pages.created_at')->get(),
        ]);
    }

    public function responses(Request $request, Page $page): View
    {
        $this->authorizeAgent($request, $page);

        return view('admin.pages.responses', [
            'page' => $page,
            'responses' => $page->responses()->latest()->paginate(40),
            'backUrl' => route('propet.pages.index'),
            'exportUrl' => route('propet.pages.responses.export', $page),
        ]);
    }

    public function exportResponses(Request $request, Page $page): StreamedResponse
    {
        $this->authorizeAgent($request, $page);

        return PageResponsesExport::download($page);
    }

    /** Un agent ne voit que les vues qui lui sont attribuées. */
    private function authorizeAgent(Request $request, Page $page): void
    {
        abort_unless($page->agents()->whereKey($request->user()->id)->exists(), 404);
    }
}
