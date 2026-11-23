<?php

namespace App\Mail;

use App\Models\Campaign;
use App\Models\Setting;
use App\Support\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class CampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $unsubscribeUrl;

    /**
     * @param  string|null  $formUrl  lien personnel vers le formulaire de réponse (jeton du destinataire)
     */
    public function __construct(public Campaign $campaign, public string $recipientEmail, public ?string $formUrl = null)
    {
        $this->unsubscribeUrl = URL::signedRoute('unsubscribe.show', ['email' => $recipientEmail]);
    }

    public function envelope(): Envelope
    {
        $replyTo = Setting::get('mail_reply_to');

        return new Envelope(
            from: new Address(config('mail.from.address'), Setting::get('mail_from_name') ?: config('mail.from.name')),
            replyTo: $replyTo ? [new Address($replyTo)] : [],
            subject: self::fill($this->campaign->subject, $this->recipientEmail),
        );
    }

    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.$this->unsubscribeUrl.'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign',
            text: 'emails.campaign-text',
            with: self::viewData(
                body: $this->campaign->body,
                email: $this->recipientEmail,
                logoPath: $this->campaign->logo_path,
                buttonText: $this->campaign->button_text,
                buttonUrl: $this->campaign->button_url,
                unsubscribeUrl: $this->unsubscribeUrl,
                form: $this->campaign->hasForm() ? $this->campaign->form : null,
                formUrl: $this->formUrl,
            ),
        );
    }

    /**
     * Données de la vue, partagées entre l'envoi réel et l'aperçu.
     * Le logo du message est prioritaire sur le logo global.
     *
     * @param  array<string,mixed>  $brandingOverrides
     * @return array<string,mixed>
     */
    public static function viewData(
        string $body, string $email, ?string $logoPath, ?string $buttonText, ?string $buttonUrl,
        string $unsubscribeUrl, array $brandingOverrides = [], ?array $form = null, ?string $formUrl = null,
    ): array {
        $branding = MailBranding::current($brandingOverrides);
        $hasForm = $form !== null && \App\Support\ResponseForm::isActive($form) && $formUrl;

        // Les variables {email} {date} {app} sont résolues aussi dans le libellé du logo et le bloc d'infos.
        $branding['logo_label'] = $branding['logo_label'] ? self::fill($branding['logo_label'], $email) : null;
        $branding['info_lines'] = array_map(
            fn ($l) => ['label' => $l['label'], 'value' => self::fill($l['value'], $email)],
            $branding['info_lines'],
        );

        return [
            'body' => self::fill($body, $email, $hasForm ? $formUrl : null),
            'formUrl' => $hasForm ? $formUrl : null,
            'formButton' => $hasForm ? $form['button_text'] : null,
            'branding' => $branding,
            'logoFile' => MailBranding::absolutePath($logoPath) ?? MailBranding::absolutePath($branding['logo_path']),
            'buttonText' => $buttonText,
            'buttonUrl' => $buttonUrl,
            'unsubscribeUrl' => $unsubscribeUrl,
        ];
    }

    public static function fill(string $text, string $email, ?string $formUrl = null): string
    {
        return strtr($text, [
            '{lien}' => $formUrl ?? '',
            '{email}' => $email,
            '{date}' => now()->format('d/m/Y'),
            '{app}' => config('app.name'),
        ]);
    }
}
