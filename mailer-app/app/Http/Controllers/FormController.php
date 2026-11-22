<?php

namespace App\Http\Controllers;

use App\Enums\CampaignStatus;
use App\Models\CampaignRecipient;
use App\Models\Response;
use App\Models\Setting;
use App\Support\MailBranding;
use App\Support\ResponseForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Page publique ouverte depuis le lien de l'email.
 * Le jeton du lien identifie le destinataire : il n'a jamais à saisir son adresse.
 */
class FormController extends Controller
{
    public function show(string $token): View
    {
        $recipient = $this->recipient($token);
        $recipient->form_opened_at ??= now();
        $recipient->save();

        return view('forms.show', $this->viewData($recipient));
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $recipient = $this->recipient($token);
        $form = $recipient->campaign->form;

        abort_if($this->isClosed($recipient), 403, 'Ce formulaire est fermé.');

        $validated = $request->validate(ResponseForm::rules($form), [], ResponseForm::attributes($form));
        $answers = ResponseForm::snapshot($form, $validated['answers'] ?? []);

        $existing = $recipient->response;
        if ($existing) {
            $existing->update(['answers' => $answers, 'edits' => $existing->edits + 1]);
        } else {
            Response::create([
                'campaign_id' => $recipient->campaign_id,
                'campaign_recipient_id' => $recipient->id,
                'email' => $recipient->email, // pris du jeton, jamais du formulaire
                'answers' => $answers,
            ]);
        }

        // Redirection définie par l'admin, sinon message de remerciement sur place.
        if (! empty($form['redirect_url'])) {
            return redirect()->away($form['redirect_url']);
        }

        return redirect()->route('form.show', $token)->with('thanks', true);
    }

    private function recipient(string $token): CampaignRecipient
    {
        $recipient = CampaignRecipient::with(['campaign', 'response'])
            ->where('token', $token)
            ->firstOrFail();

        abort_unless($recipient->campaign->hasForm(), 404);

        return $recipient;
    }

    private function isClosed(CampaignRecipient $r): bool
    {
        return $r->campaign->status === CampaignStatus::Rejected;
    }

    /** @return array<string,mixed> */
    private function viewData(CampaignRecipient $r): array
    {
        $answers = collect($r->response?->answers ?? [])->pluck('value', 'key')->all();
        $b = MailBranding::current();

        return [
            'form' => $r->campaign->form,
            'maskedEmail' => self::mask($r->email),
            'answers' => $answers,
            'response' => $r->response,
            'branding' => $b,
            'logo' => MailBranding::dataUri($r->campaign->logo_path) ?? MailBranding::dataUri(Setting::get('mail_logo_path')),
            'logoAlign' => $b['logo_align'],
            'action' => route('form.store', $r->token),
            'closed' => $this->isClosed($r),
        ];
    }

    /** jean.dupont@orange.fr → j*********t@orange.fr (lien transférable : on n'affiche pas l'adresse en clair) */
    public static function mask(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $len = mb_strlen($local);

        return $len <= 2
            ? str_repeat('*', $len).'@'.$domain
            : mb_substr($local, 0, 1).str_repeat('*', $len - 2).mb_substr($local, -1).'@'.$domain;
    }
}
