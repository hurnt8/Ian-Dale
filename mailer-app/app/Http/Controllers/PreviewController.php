<?php

namespace App\Http\Controllers;

use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/** Aperçu HTML exact de l'email (affiché dans une iframe). */
class PreviewController extends Controller
{
    public function template(Request $request, MessageTemplate $template): Response
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $template->is_active, 404);

        // Seul l'admin peut prévisualiser des réglages de présentation non enregistrés
        $overrides = $user->isAdmin() ? $request->query() : [];

        return $this->render($template->body, $template->logo_path, $template->button_text, $template->button_url, $template->form, $overrides);
    }

    public function campaign(Campaign $campaign): Response
    {
        Gate::authorize('view', $campaign);

        return $this->render($campaign->body, $campaign->logo_path, $campaign->button_text, $campaign->button_url, $campaign->form);
    }

    /** Aperçu du formulaire tel que le verra le destinataire (aucune réponse enregistrée). */
    public function form(Request $request, MessageTemplate $template): \Illuminate\View\View
    {
        abort_unless($request->user()->isAdmin() || $template->is_active, 404);
        abort_unless($template->hasForm(), 404);

        $b = \App\Support\MailBranding::current();

        return view('forms.show', [
            'form' => $template->form,
            'maskedEmail' => 'd*********@exemple.fr',
            'answers' => [],
            'response' => null,
            'branding' => $b,
            'logo' => \App\Support\MailBranding::dataUri($template->logo_path) ?? \App\Support\MailBranding::dataUri(\App\Models\Setting::get('mail_logo_path')),
            'logoAlign' => $b['logo_align'],
            'action' => null, // aperçu : pas d'envoi
            'closed' => false,
        ]);
    }

    /** Aperçu d'une « vue » publique (admin uniquement, même avant publication). */
    public function page(Request $request, \App\Models\Page $page): \Illuminate\View\View
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($page->hasForm(), 404);

        $b = \App\Support\MailBranding::current();

        return view('pages.show', [
            'form' => $page->form,
            'branding' => $b,
            'logo' => \App\Support\MailBranding::dataUri($page->logo_path) ?? \App\Support\MailBranding::dataUri(\App\Models\Setting::get('mail_logo_path')),
            'logoAlign' => $b['logo_align'],
            'action' => null, // aperçu : rien n'est enregistré
        ]);
    }

    /** @param array<string,mixed> $overrides */
    private function render(string $body, ?string $logo, ?string $btnText, ?string $btnUrl, ?array $form, array $overrides = []): Response
    {
        $html = view('emails.campaign', CampaignMail::viewData(
            body: $body,
            email: 'destinataire@exemple.fr',
            logoPath: $logo,
            buttonText: $btnText,
            buttonUrl: $btnUrl,
            unsubscribeUrl: '#',
            brandingOverrides: $overrides,
            form: $form,
            formUrl: '#',
        ))->render();

        return response($html)->header('X-Frame-Options', 'SAMEORIGIN');
    }
}
