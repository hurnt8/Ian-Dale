<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageResponse extends Model
{
    protected $fillable = ['page_id', 'user_id', 'answers', 'ip'];

    protected $casts = ['answers' => 'array'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /** Agent dont le lien personnel a servi à répondre (null = lien général). */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Réponses qu'un utilisateur peut consulter :
     *  - admin : toutes
     *  - agent : celles reçues par SON lien, plus celles du lien général des vues qui lui sont attribuées
     */
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $q;
        }

        return $q->where(fn (Builder $w) => $w
            ->where('page_responses.user_id', $user->id)
            ->orWhere(fn (Builder $g) => $g
                ->whereNull('page_responses.user_id')
                ->whereIn('page_responses.page_id', $user->assignedPages()->select('pages.id'))));
    }
}
