<?php

namespace App\Models;

use App\Support\ResponseForm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Une « vue » : page publique autonome (titre, champs, bouton) accessible à /vue/{slug}.
 * Les champs sont paramétrés avec le même constructeur que les formulaires d'email.
 */
class Page extends Model
{
    protected $fillable = ['slug', 'form', 'logo_path', 'is_published'];

    protected $casts = [
        'form' => 'array',
        'is_published' => 'bool',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(PageResponse::class);
    }

    /** Agents (propets) autorisés à consulter les réponses de cette vue. */
    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /** Lien personnel de l'agent : les réponses reçues par ce lien lui reviennent. */
    public function linkFor(User $agent): string
    {
        return route('page.show', ['slug' => $this->slug, \App\Support\AgentLink::PARAM => \App\Support\AgentLink::code($agent)]);
    }

    /** Vues qu'un agent peut partager avec son lien : celles que l'admin lui a attribuées. */
    public function scopeForAgent(Builder $query, User $agent): Builder
    {
        return $query->whereIn('pages.id', $agent->assignedPages()->select('pages.id'));
    }

    public function hasForm(): bool
    {
        return ResponseForm::isActive($this->form);
    }

    /** Titre affiché (défini dans le constructeur de champs). */
    public function title(): string
    {
        return $this->form['title'] ?? 'Vue sans titre';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
