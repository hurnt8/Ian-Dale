<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageResponse;
use App\Models\Setting;
use App\Support\MailBranding;
use App\Support\ResponseForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Affichage et soumission d'une « vue » publique (anonyme).
 */
class PublicPageController extends Controller
{
    public function show(string $slug): View
    {
        return view('pages.show', $this->viewData($this->page($slug)));
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $page = $this->page($slug);
        $form = $page->form;

        $validated = $request->validate(ResponseForm::rules($form), [], ResponseForm::attributes($form));

        PageResponse::create([
            'page_id' => $page->id,
            'answers' => ResponseForm::snapshot($form, $validated['answers'] ?? []),
            'ip' => $request->ip(),
        ]);

        // Si l'admin a défini une redirection, on y envoie le visiteur ; sinon on affiche le message.
        if (! empty($form['redirect_url'])) {
            return redirect()->away($form['redirect_url']);
        }

        return redirect()->route('page.show', $page->slug)->with('thanks', true);
    }

    private function page(string $slug): Page
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();
        abort_unless($page->hasForm(), 404);

        return $page;
    }

    /** @return array<string,mixed> */
    private function viewData(Page $page): array
    {
        $b = MailBranding::current();

        return [
            'form' => $page->form,
            'branding' => $b,
            'logo' => MailBranding::dataUri($page->logo_path) ?? MailBranding::dataUri(Setting::get('mail_logo_path')),
            'logoAlign' => $b['logo_align'],
            'action' => route('page.store', $page->slug),
        ];
    }
}
