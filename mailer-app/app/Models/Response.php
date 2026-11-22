<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Response extends Model
{
    protected $fillable = ['campaign_id', 'campaign_recipient_id', 'email', 'answers', 'edits'];

    protected $casts = ['answers' => 'array'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(CampaignRecipient::class, 'campaign_recipient_id');
    }

    /** Un propet ne voit que les réponses à SES listes ; l'admin voit tout. */
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        return $user->isAdmin() ? $q : $q->whereHas('campaign', fn (Builder $c) => $c->where('user_id', $user->id));
    }
}
