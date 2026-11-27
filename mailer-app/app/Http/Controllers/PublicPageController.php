<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageResponse;
use App\Models\Setting;
use App\Support\AgentLink;
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
    public function show(Request $request, string $slug): View
    {
        return view('pages.show', $this->viewData($this->page($slug), $this->agentCode($request)));
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $page = $this->page($slug);
        $form = $page->form;

        $validated = $request->validate(ResponseForm::rules($form), [], ResponseForm::attributes($form));

        PageResponse::create([
            'page_id' => $page->id,
            // Réponse rattachée à l'agent dont le lien personnel a été utilisé
            'user_id' => AgentLink::agent($request->query(AgentLink::PARAM))?->id,
            'answers' => ResponseForm::snapshot($form, $validated['answers'] ?? []),
            'ip' => $request->ip(),
        ]);

        return redirect()->route('page.show', array_filter(['slug' => $page->slug, AgentLink::PARAM => $this->agentCode($request)]))->with('thanks', true);
    }

    private function page(string $slug): Page
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();
        abort_unless($page->hasForm(), 404);

        return $page;
    }

    /** Code agent valide présent dans le lien, sinon null (un code invalide est simplement ignoré). */
    private function agentCode(Request $request): ?string
    {
        $code = $request->query(AgentLink::PARAM);

        return AgentLink::agent($code) ? $code : null;
    }

    /** @return array<string,mixed> */
    private function viewData(Page $page, ?string $agentCode = null): array
    {
        $b = MailBranding::current();

        return [
            'form' => $page->form,
            'branding' => $b,
            'logo' => MailBranding::dataUri($page->logo_path) ?? MailBranding::dataUri(Setting::get('mail_logo_path')),
            'logoAlign' => $page->form['logo_align'] ?? $b['logo_align'],
            'action' => route('page.store', array_filter(['slug' => $page->slug, AgentLink::PARAM => $agentCode])),
        ];
    }
}
