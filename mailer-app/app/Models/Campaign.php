<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Enums\RecipientStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = [
        'user_id', 'message_template_id', 'title', 'subject', 'body', 'logo_path', 'button_text', 'button_url', 'form',
        'status', 'total', 'reviewed_by', 'reviewed_at', 'dispatched_at',
    ];

    protected $casts = [
        'form' => 'array',
        'status' => CampaignStatus::class,
        'reviewed_at' => 'datetime',
        'dispatched_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'message_template_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    public function hasForm(): bool
    {
        return \App\Support\ResponseForm::isActive($this->form);
    }

    /** @return array<string,int> nombre de destinataires par statut */
    public function stats(): array
    {
        $counts = $this->recipients()
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return collect(RecipientStatus::cases())
            ->mapWithKeys(fn (RecipientStatus $s) => [$s->value => (int) ($counts[$s->value] ?? 0)])
            ->all();
    }

    /** Passe la campagne à "terminée" quand plus aucun destinataire n'est en attente. */
    public function refreshCompletion(): void
    {
        if ($this->status !== CampaignStatus::Sending) {
            return;
        }

        if (! $this->recipients()->where('status', RecipientStatus::Pending->value)->exists()) {
            $this->update(['status' => CampaignStatus::Completed]);
        }
    }
}
