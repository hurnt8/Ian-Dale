<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Définition d'un formulaire de réponse dont l'admin paramètre les champs (libellés, types, options).
 *
 * Structure stockée (JSON) :
 * {
 *   enabled, title, intro, button_text, submit_label, thanks, show_bar, accent_color,
 *   fields: [{key, label, type, required, placeholder, options: []}]
 * }
 */
final class ResponseForm
{
    public const TYPES = [
        'text' => 'Texte court',
        'textarea' => 'Texte long',
        'rating' => 'Note (1 à 5 étoiles)',
        'yesno' => 'Oui / Non',
        'radio' => 'Choix unique (boutons)',
        'select' => 'Liste déroulante',
        'checkbox' => 'Choix multiples (cases)',
        'number' => 'Nombre',
        'date' => 'Date',
        'tel' => 'Téléphone',
        'email' => 'Autre adresse email',
    ];

    public const WITH_OPTIONS = ['radio', 'select', 'checkbox'];

    public const DEFAULTS = [
        'enabled' => false,
        'title' => 'Donnez-nous votre avis',
        'intro' => 'Votre réponse ne prend qu’une minute.',
        'button_text' => 'Donner mon avis',
        'submit_label' => 'Envoyer ma réponse',
        'thanks' => 'Merci, votre réponse a bien été enregistrée.',
        'show_bar' => true,          // bandeau de couleur en haut de la page du formulaire
        'accent_color' => '#ff7900', // couleur du bandeau, du badge et des champs actifs
        'fields' => [],
    ];

    /**
     * Nettoie la configuration envoyée par le formulaire admin.
     *
     * @param  array<string,mixed>|null  $input
     * @return array<string,mixed>
     */
    public static function normalize(?array $input): array
    {
        $input ??= [];
        $form = [
            'enabled' => (bool) ($input['enabled'] ?? false),
            'title' => self::str($input['title'] ?? null, 150) ?: self::DEFAULTS['title'],
            'intro' => self::str($input['intro'] ?? null, 1000),
            'button_text' => self::str($input['button_text'] ?? null, 60) ?: self::DEFAULTS['button_text'],
            'submit_label' => self::str($input['submit_label'] ?? null, 60) ?: self::DEFAULTS['submit_label'],
            'thanks' => self::str($input['thanks'] ?? null, 500) ?: self::DEFAULTS['thanks'],
            'show_bar' => array_key_exists('show_bar', $input) ? (bool) $input['show_bar'] : self::DEFAULTS['show_bar'],
            'accent_color' => self::color($input['accent_color'] ?? null),
            'fields' => [],
        ];

        $used = [];
        foreach (array_values($input['fields'] ?? []) as $f) {
            $label = self::str($f['label'] ?? null, 150);
            $type = $f['type'] ?? 'text';
            if ($label === '' || ! array_key_exists($type, self::TYPES)) {
                continue;
            }

            // Clé technique stable : conservée si fournie, sinon dérivée du libellé
            $key = Str::slug(($f['key'] ?? '') ?: $label, '_') ?: 'champ';
            $base = $key;
            for ($i = 2; in_array($key, $used, true); $i++) {
                $key = $base.'_'.$i;
            }
            $used[] = $key;

            $options = [];
            if (in_array($type, self::WITH_OPTIONS, true)) {
                $raw = is_array($f['options'] ?? null) ? $f['options'] : preg_split('/\r\n|\n|;/', (string) ($f['options'] ?? ''));
                $options = array_values(array_unique(array_filter(array_map(fn ($o) => self::str($o, 100), $raw))));
                if ($options === []) {
                    continue; // un choix sans option n'a pas de sens
                }
            }

            $form['fields'][] = [
                'key' => $key,
                'label' => $label,
                'type' => $type,
                'required' => (bool) ($f['required'] ?? false),
                'placeholder' => self::str($f['placeholder'] ?? null, 150),
                'options' => $options,
            ];

            if (count($form['fields']) >= 30) {
                break;
            }
        }

        if ($form['fields'] === []) {
            $form['enabled'] = false;
        }

        return $form;
    }

    /** Couleur hexadécimale #rrggbb, sinon la couleur par défaut. */
    public static function color(mixed $value): string
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : self::DEFAULTS['accent_color'];
    }

    /** @param array<string,mixed>|null $form */
    public static function isActive(?array $form): bool
    {
        return ! empty($form['enabled']) && ! empty($form['fields']);
    }

    /**
     * Règles de validation construites à partir des champs paramétrés.
     *
     * @param  array<string,mixed>  $form
     * @return array<string,mixed>
     */
    public static function rules(array $form): array
    {
        $rules = [];
        foreach ($form['fields'] as $f) {
            $base = [$f['required'] ? 'required' : 'nullable'];
            $k = 'answers.'.$f['key'];

            $rules[$k] = match ($f['type']) {
                'textarea' => [...$base, 'string', 'max:5000'],
                'rating' => [...$base, 'integer', 'between:1,5'],
                'yesno' => [...$base, Rule::in(['oui', 'non'])],
                'radio', 'select' => [...$base, Rule::in($f['options'])],
                'checkbox' => [...$base, 'array'],
                'number' => [...$base, 'numeric'],
                'date' => [...$base, 'date'],
                'tel' => [...$base, 'string', 'max:30', 'regex:/^[0-9+().\s-]{6,30}$/'],
                'email' => [...$base, 'email', 'max:190'],
                default => [...$base, 'string', 'max:500'],
            };

            if ($f['type'] === 'checkbox') {
                $rules[$k.'.*'] = [Rule::in($f['options'])];
            }
        }

        return $rules;
    }

    /** @return array<string,string> libellés lisibles dans les messages d'erreur */
    public static function attributes(array $form): array
    {
        return collect($form['fields'])->mapWithKeys(fn ($f) => ['answers.'.$f['key'] => '« '.$f['label'].' »'])->all();
    }

    /**
     * Transforme les réponses validées en liste figée (le libellé est conservé même si le formulaire change).
     *
     * @param  array<string,mixed>  $form
     * @param  array<string,mixed>  $answers
     * @return list<array{key:string,label:string,type:string,value:mixed}>
     */
    public static function snapshot(array $form, array $answers): array
    {
        return array_map(fn ($f) => [
            'key' => $f['key'],
            'label' => $f['label'],
            'type' => $f['type'],
            'value' => $answers[$f['key']] ?? null,
        ], $form['fields']);
    }

    /** Valeur lisible pour l'affichage / l'export. */
    public static function display(array $answer): string
    {
        $v = $answer['value'] ?? null;

        return match (true) {
            $v === null || $v === '' || $v === [] => '—',
            is_array($v) => implode(', ', $v),
            ($answer['type'] ?? '') === 'rating' => str_repeat('★', (int) $v).str_repeat('☆', 5 - (int) $v)." ($v/5)",
            ($answer['type'] ?? '') === 'yesno' => ucfirst((string) $v),
            default => (string) $v,
        };
    }

    private static function str(mixed $v, int $max): string
    {
        return is_scalar($v) ? mb_substr(trim((string) $v), 0, $max) : '';
    }
}
