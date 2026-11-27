<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class MessageTemplate extends Model
{
    protected $fillable = ['user_id', 'name', 'subject', 'body', 'is_default', 'is_active', 'logo_path', 'button_text', 'button_url', 'form'];

    protected $casts = ['is_default' => 'boolean', 'is_active' => 'boolean', 'form' => 'array'];

    public function hasForm(): bool
    {
        return \App\Support\ResponseForm::isActive($this->form);
    }

    /** Agent auteur du message (null = message de l'admin, partagé). */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isPersonal(): bool
    {
        return $this->user_id !== null;
    }

    /** L'utilisateur peut-il voir / prévisualiser ce message ? */
    public function isVisibleTo(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->isPersonal() ? $this->user_id === $user->id : $this->is_active;
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /** Réponses reçues via le formulaire, toutes listes envoyées avec ce message confondues. */
    public function responses(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Response::class, Campaign::class);
    }

    /** Messages de l'admin (partagés), par opposition aux messages personnels des agents. */
    public function scopeShared(Builder $q): Builder
    {
        return $q->whereNull('message_templates.user_id');
    }

    /** Messages partagés (de l'admin) que les propets peuvent choisir. */
    public function scopeAvailable(Builder $q): Builder
    {
        return $q->shared()->where('is_active', true)->orderByDesc('is_default')->orderBy('name');
    }

    public static function default(): ?self
    {
        return static::query()->shared()->where('is_default', true)->first() ?? static::query()->shared()->first();
    }

    public function makeDefault(): void
    {
        DB::transaction(function (): void {
            static::query()->shared()->whereKeyNot($this->id)->update(['is_default' => false]);
            $this->update(['is_default' => true]);
        });
    }
}
