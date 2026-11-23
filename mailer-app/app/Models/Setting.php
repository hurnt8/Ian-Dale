<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        // --- Comportement ---
        'require_approval' => '0',  // envoi automatique par défaut (mettre à 1 pour exiger une validation admin)
        'per_minute' => '20',       // nombre max d'emails envoyés par minute
        'max_recipients' => '500',  // taille max d'une liste soumise
        'skip_invalid' => '1',      // ne pas envoyer aux adresses dont la vérification échoue
        'skip_typo' => '0',         // ne pas envoyer aux adresses suspectes (faute de frappe)
        'orange_only' => '1',       // n'envoyer qu'aux destinataires Orange
        'orange_domains' => 'orange.fr, wanadoo.fr', // domaines considérés comme Orange
        'mail_from_name' => null,   // nom d'expéditeur (défaut : nom de l'application)
        'mail_reply_to' => null,    // adresse de réponse

        // --- Présentation de l'email ---
        'mail_logo_path' => null,
        'mail_logo_width' => '160',
        'mail_logo_align' => 'center',
        'mail_logo_label' => null,  // texte affiché à côté du logo
        'mail_logo_label_valign' => 'middle', // alignement vertical du texte à côté du logo (top|middle|bottom)
        'mail_info_lines' => null,  // bloc d'informations (JSON : [{label, value}])
        'mail_primary_color' => '#4f46e5',
        'mail_bg_color' => '#f4f5fa',
        'mail_card_color' => '#ffffff',
        'mail_text_color' => '#1f2333',
        'mail_font' => 'Arial, Helvetica, sans-serif',
        'mail_header_text' => null,

    ];

    public static function get(string $key): ?string
    {
        return Cache::rememberForever("setting.$key", function () use ($key) {
            return static::query()->find($key)?->value ?? (self::DEFAULTS[$key] ?? null);
        });
    }

    public static function put(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.$key");
    }

    public static function bool(string $key): bool
    {
        return (bool) (int) static::get($key);
    }

    public static function int(string $key): int
    {
        return (int) static::get($key);
    }
}
