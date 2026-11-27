<?php

namespace App\Http\Controllers\Propet;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageResponse;
use App\Support\PageResponsesExport;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Vues que l'agent peut partager avec SON lien personnel, et les réponses qui lui reviennent (lecture seule).
 */
class PageController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('propet.pages.index', [
            'pages' => Page::forAgent($user)
                ->withCount(['responses' => fn ($q) => $q->visibleTo($user)])
                ->latest('pages.created_at')
                ->get(),
        ]);
    }

    public function responses(Request $request, Page $page): View
    {
        $this->authorizeAgent($request, $page);

        return view('admin.pages.responses', [
            'page' => $page,
            'responses' => $page->responses()->visibleTo($request->user())->latest()->paginate(40),
            'backUrl' => route('propet.pages.index'),
            'exportUrl' => route('propet.pages.responses.export', $page),
            'shareUrl' => $page->is_published ? $page->linkFor($request->user()) : null,
        ]);
    }

    public function exportResponses(Request $request, Page $page): StreamedResponse
    {
        $this->authorizeAgent($request, $page);

        return PageResponsesExport::download($page, PageResponse::query()->visibleTo($request->user()));
    }

    /** Un agent n'accède qu'aux vues publiées ou qui lui sont attribuées. */
    private function authorizeAgent(Request $request, Page $page): void
    {
        abort_unless(Page::forAgent($request->user())->whereKey($page->id)->exists(), 404);
    }
}
