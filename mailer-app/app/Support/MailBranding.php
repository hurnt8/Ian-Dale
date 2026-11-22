<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Présentation des emails définie par l'admin (couleurs, logo, en-tête, pied de page).
 */
final class MailBranding
{
    public const FONTS = [
        "'HelvNeueOrange', 'Helvetica Neue', Arial, sans-serif" => 'HelvNeueOrange (Orange)',
        'Arial, Helvetica, sans-serif' => 'Arial',
        'Verdana, Geneva, sans-serif' => 'Verdana',
        'Tahoma, Geneva, sans-serif' => 'Tahoma',
        "'Trebuchet MS', Helvetica, sans-serif" => 'Trebuchet MS',
        'Georgia, serif' => 'Georgia',
        "'Times New Roman', Times, serif" => 'Times New Roman',
    ];

    public const COLOR_KEYS = ['mail_primary_color', 'mail_bg_color', 'mail_card_color', 'mail_text_color'];

    /**
     * @param  array<string,mixed>  $overrides  valeurs non enregistrées (aperçu en direct)
     * @return array<string,mixed>
     */
    public static function current(array $overrides = []): array
    {
        $b = [
            'logo_path' => Setting::get('mail_logo_path'),
            'logo_width' => (int) Setting::get('mail_logo_width'),
            'logo_align' => Setting::get('mail_logo_align'),
            'primary' => Setting::get('mail_primary_color'),
            'bg' => Setting::get('mail_bg_color'),
            'card' => Setting::get('mail_card_color'),
            'text' => Setting::get('mail_text_color'),
            'font' => Setting::get('mail_font'),
            'header' => Setting::get('mail_header_text'),
            'footer' => Setting::get('mail_footer_text'),
        ];

        $map = [
            'mail_logo_width' => 'logo_width', 'mail_logo_align' => 'logo_align',
            'mail_primary_color' => 'primary', 'mail_bg_color' => 'bg', 'mail_card_color' => 'card',
            'mail_text_color' => 'text', 'mail_font' => 'font', 'mail_header_text' => 'header', 'mail_footer_text' => 'footer',
        ];

        foreach ($map as $key => $target) {
            if (! array_key_exists($key, $overrides)) {
                continue;
            }
            $v = $overrides[$key];
            $ok = match (true) {
                in_array($key, self::COLOR_KEYS, true) => is_string($v) && self::isColor($v),
                $key === 'mail_font' => array_key_exists($v, self::FONTS),
                $key === 'mail_logo_align' => in_array($v, ['left', 'center', 'right'], true),
                $key === 'mail_logo_width' => is_numeric($v) && $v >= 40 && $v <= 600,
                default => is_string($v) || $v === null,
            };
            if ($ok) {
                $b[$target] = $key === 'mail_logo_width' ? (int) $v : $v;
            }
        }

        return $b;
    }

    public static function isColor(string $v): bool
    {
        return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $v);
    }

    /** Chemin absolu d'un logo stocké, ou null s'il n'existe pas. */
    public static function absolutePath(?string $relative): ?string
    {
        if (! $relative || ! Storage::disk('local')->exists($relative)) {
            return null;
        }

        return Storage::disk('local')->path($relative);
    }

    public static function dataUri(?string $relative): ?string
    {
        $path = self::absolutePath($relative);

        return $path ? 'data:'.mime_content_type($path).';base64,'.base64_encode((string) file_get_contents($path)) : null;
    }

    /** Enregistre un logo (stockage privé : il est intégré aux emails, pas servi publiquement). */
    public static function storeLogo(UploadedFile $file, ?string $previous = null): string
    {
        if ($previous) {
            Storage::disk('local')->delete($previous);
        }

        return $file->store('logos', 'local');
    }
}
