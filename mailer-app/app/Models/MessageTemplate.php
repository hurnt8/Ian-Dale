<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class MessageTemplate extends Model
{
    protected $fillable = ['name', 'subject', 'body', 'is_default', 'is_active', 'logo_path', 'button_text', 'button_url', 'form'];

    protected $casts = ['is_default' => 'boolean', 'is_active' => 'boolean', 'form' => 'array'];

    public function hasForm(): bool
    {
        return \App\Support\ResponseForm::isActive($this->form);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /** Messages que les propets peuvent choisir. */
    public function scopeAvailable(\Illuminate\Database\Eloquent\Builder $q): \Illuminate\Database\Eloquent\Builder
    {
        return $q->where('is_active', true)->orderByDesc('is_default')->orderBy('name');
    }

    public static function default(): ?self
    {
        return static::query()->where('is_default', true)->first() ?? static::query()->first();
    }

    public function makeDefault(): void
    {
        DB::transaction(function (): void {
            static::query()->whereKeyNot($this->id)->update(['is_default' => false]);
            $this->update(['is_default' => true]);
        });
    }
}
