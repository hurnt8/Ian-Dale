<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case Valid = 'valid';                    // syntaxe OK + domaine avec serveur mail (MX)
    case Risky = 'risky';                    // domaine existe mais pas de MX déclaré
    case Typo = 'typo';                      // faute de frappe probable (ex. gmial.com)
    case InvalidSyntax = 'invalid_syntax';
    case DomainNotFound = 'domain_not_found';
    case Disposable = 'disposable';          // adresse jetable (yopmail…)
    case MailboxNotFound = 'mailbox_not_found'; // rejet définitif du serveur lors d'un envoi

    public function label(): string
    {
        return match ($this) {
            self::Valid => 'Valide',
            self::Risky => 'Risqué',
            self::Typo => 'Faute de frappe ?',
            self::InvalidSyntax => 'Syntaxe invalide',
            self::DomainNotFound => 'Domaine inexistant',
            self::Disposable => 'Adresse jetable',
            self::MailboxNotFound => 'Boîte inexistante',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Valid => '#047857',
            self::Risky, self::Typo => '#b45309',
            default => '#b91c1c',
        };
    }

    /** Adresse à ne pas envoyer. */
    public function isBlocking(): bool
    {
        return in_array($this, [self::InvalidSyntax, self::DomainNotFound, self::Disposable, self::MailboxNotFound], true);
    }
}
