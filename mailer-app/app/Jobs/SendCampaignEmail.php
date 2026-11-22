<?php

namespace App\Jobs;

use App\Enums\RecipientStatus;
use App\Enums\VerificationStatus;
use App\Mail\CampaignMail;
use App\Models\CampaignRecipient;
use App\Models\Unsubscribe;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendCampaignEmail implements ShouldQueue
{
    use Queueable;

    /** Nombre d'erreurs réelles (SMTP...) tolérées avant d'abandonner. */
    public int $maxExceptions = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(public int $recipientId) {}

    /** Le RateLimited remet le job en file : on autorise donc les relances pendant 2 jours. */
    public function retryUntil(): DateTimeInterface
    {
        return now()->addDays(2);
    }

    public function middleware(): array
    {
        return [new RateLimited('campaign-mail')];
    }

    public function handle(): void
    {
        $recipient = CampaignRecipient::with('campaign')->find($this->recipientId);

        if (! $recipient || $recipient->status !== RecipientStatus::Pending) {
            return; // déjà traité (idempotence)
        }

        if (Unsubscribe::isUnsubscribed($recipient->email)) {
            $recipient->update(['status' => RecipientStatus::Skipped]);
            $recipient->campaign->refreshCompletion();

            return;
        }

        try {
            $formUrl = $recipient->campaign->hasForm() ? $recipient->formUrl() : null;
            Mail::to($recipient->email)->send(new CampaignMail($recipient->campaign, $recipient->email, $formUrl));
        } catch (Throwable $e) {
            if (! self::isHardBounce($e->getMessage())) {
                throw $e; // erreur temporaire : nouvelle tentative plus tard
            }

            // Le serveur destinataire affirme que la boîte n'existe pas : on ne réessaie jamais.
            $recipient->update([
                'status' => RecipientStatus::Invalid,
                'verification' => VerificationStatus::MailboxNotFound,
                'verification_detail' => 'Rejet du serveur destinataire : boîte inexistante.',
                'error' => mb_substr($e->getMessage(), 0, 1000),
            ]);
            Unsubscribe::firstOrCreate(['email' => $recipient->email], ['reason' => 'hard_bounce']);
            $recipient->campaign->refreshCompletion();

            return;
        }

        $recipient->update([
            'status' => RecipientStatus::Sent,
            'sent_at' => now(),
            'error' => null,
        ]);

        $recipient->campaign->refreshCompletion();
    }

    /** Codes SMTP de rejet définitif « destinataire inconnu ». */
    public static function isHardBounce(string $error): bool
    {
        return (bool) preg_match('/\b55[0-3]\b.*(user|mailbox|recipient|address|destinataire)|5\.1\.[0-3]|user unknown|no such user|does not exist|mailbox (unavailable|not found)|adresse (inconnue|inexistante)/i', $error);
    }

    public function failed(?Throwable $e): void
    {
        $recipient = CampaignRecipient::with('campaign')->find($this->recipientId);

        if (! $recipient) {
            return;
        }

        $recipient->update([
            'status' => RecipientStatus::Failed,
            'error' => mb_substr((string) $e?->getMessage(), 0, 1000),
        ]);

        $recipient->campaign->refreshCompletion();
    }
}
