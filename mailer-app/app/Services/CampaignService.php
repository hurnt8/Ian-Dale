<?php

namespace App\Services;

use App\Enums\CampaignStatus;
use App\Enums\RecipientStatus;
use App\Enums\VerificationStatus;
use App\Jobs\SendCampaignEmail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\MessageTemplate;
use App\Models\Setting;
use App\Models\Unsubscribe;
use App\Models\User;
use App\Services\Verification\EmailVerifier;
use Illuminate\Support\Facades\DB;

final class CampaignService
{
    public function __construct(private EmailVerifier $verifier) {}

    /**
     * Crée une campagne à partir d'une liste d'emails soumise par un propet.
     * Chaque adresse est vérifiée ; selon les paramètres, les adresses invalides ne sont pas envoyées.
     * Si la validation admin est désactivée, l'envoi démarre immédiatement.
     *
     * @param  list<string>  $emails  adresses déjà dédoublonnées
     */
    public function submit(User $propet, MessageTemplate $template, string $title, array $emails): Campaign
    {
        $results = $this->verifier->verifyMany($emails);
        $skipInvalid = Setting::bool('skip_invalid');
        $skipTypo = Setting::bool('skip_typo');

        $campaign = DB::transaction(function () use ($propet, $template, $title, $emails, $results, $skipInvalid, $skipTypo): Campaign {
            $campaign = Campaign::create([
                'user_id' => $propet->id,
                'message_template_id' => $template->id,
                'title' => $title,
                // Copie du message au moment de la soumission
                'subject' => $template->subject,
                'body' => $template->body,
                'logo_path' => $template->logo_path,
                'button_text' => $template->button_text,
                'button_url' => $template->button_url,
                'form' => $template->hasForm() ? $template->form : null,
                'status' => CampaignStatus::Pending,
                'total' => count($emails),
            ]);

            $unsubscribed = Unsubscribe::query()->whereIn('email', $emails)->pluck('email')->flip();
            $now = now();

            $rows = array_map(function ($r) use ($campaign, $unsubscribed, $now, $skipInvalid, $skipTypo) {
                $status = match (true) {
                    isset($unsubscribed[$r->email]) => RecipientStatus::Skipped,
                    $skipInvalid && $r->status->isBlocking() => RecipientStatus::Invalid,
                    $skipTypo && $r->status === VerificationStatus::Typo => RecipientStatus::Invalid,
                    default => RecipientStatus::Pending,
                };

                return [
                    'campaign_id' => $campaign->id,
                    'email' => $r->email,
                    'token' => CampaignRecipient::newToken(),
                    'status' => $status->value,
                    'verification' => $r->status->value,
                    'verification_detail' => mb_substr($r->detail, 0, 255),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $results);

            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('campaign_recipients')->insert($chunk);
            }

            return $campaign;
        });

        if (! Setting::bool('require_approval')) {
            $this->dispatch($campaign);
        }

        return $campaign;
    }

    public function approve(Campaign $campaign, User $admin): void
    {
        $campaign->update([
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $this->dispatch($campaign);
    }

    public function reject(Campaign $campaign, User $admin): void
    {
        $campaign->update([
            'status' => CampaignStatus::Rejected,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);
    }

    /** Met en file d'attente un job par destinataire en attente. */
    public function dispatch(Campaign $campaign): void
    {
        $campaign->update([
            'status' => CampaignStatus::Sending,
            'dispatched_at' => now(),
        ]);

        $campaign->recipients()
            ->where('status', RecipientStatus::Pending->value)
            ->select('id')
            ->chunkById(200, function ($recipients): void {
                foreach ($recipients as $recipient) {
                    SendCampaignEmail::dispatch($recipient->id);
                }
            });

        // Cas où aucun destinataire n'était envoyable
        $campaign->refreshCompletion();
    }

    /** Relance les envois en échec. */
    public function retryFailed(Campaign $campaign): int
    {
        $count = $campaign->recipients()
            ->where('status', RecipientStatus::Failed->value)
            ->update(['status' => RecipientStatus::Pending->value, 'error' => null]);

        if ($count > 0) {
            $this->dispatch($campaign);
        }

        return $count;
    }
}
