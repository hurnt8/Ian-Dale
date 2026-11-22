<?php

namespace App\Models;

use App\Enums\RecipientStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class CampaignRecipient extends Model
{
    protected $fillable = ['campaign_id', 'email', 'token', 'status', 'verification', 'verification_detail', 'error', 'sent_at', 'form_opened_at'];

    protected $hidden = ['token'];

    protected $casts = [
        'status' => RecipientStatus::class,
        'verification' => \App\Enums\VerificationStatus::class,
        'sent_at' => 'datetime',
        'form_opened_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $r): void {
            $r->token ??= self::newToken();
        });
    }

    public static function newToken(): string
    {
        return Str::random(48);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function response(): HasOne
    {
        return $this->hasOne(Response::class);
    }

    /** Lien personnel vers le formulaire (le destinataire n'a pas à saisir son email). */
    public function formUrl(): string
    {
        return route('form.show', $this->token);
    }
}
