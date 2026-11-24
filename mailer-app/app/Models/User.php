<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /** Messages attribués à cet agent par l'admin. */
    public function allowedTemplates(): BelongsToMany
    {
        return $this->belongsToMany(MessageTemplate::class);
    }

    /**
     * Messages que cet agent peut réellement utiliser :
     * ceux qui lui sont attribués (et actifs), ou tous les messages actifs si aucune attribution.
     *
     * @return Collection<int, MessageTemplate>
     */
    public function availableTemplates(): Collection
    {
        $assigned = $this->allowedTemplates()->where('is_active', true)
            ->orderByDesc('is_default')->orderBy('name')->get();

        return $assigned->isNotEmpty() ? $assigned : MessageTemplate::available()->get();
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isPropet(): bool
    {
        return $this->role === Role::Propet;
    }
}
