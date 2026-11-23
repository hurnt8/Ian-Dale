# Mailer Propet (Laravel 12)

Deux rôles :

- **Propet** : soumet une liste d'emails (collée telle quelle : virgules, retours à la ligne, guillemets…). Il suit l'avancement de l'envoi.
- **Admin** : configure le message automatique, valide ou refuse les listes, gère les utilisateurs et les paramètres d'envoi.

## Fonctionnement

1. Le propet colle sa liste → nettoyage automatique (minuscules, doublons, adresses invalides signalées).
2. Si « validation admin » est activée (par défaut), la liste attend. Sinon l'envoi démarre tout de suite.
3. À la validation, un job est mis en file par destinataire. Le worker envoie le **message par défaut** (Admin > Messages), au débit réglé dans Admin > Paramètres.
4. Chaque email contient un lien de désinscription (et l'en-tête `List-Unsubscribe`). Les adresses désinscrites sont ignorées dans toutes les campagnes suivantes.

Variables utilisables dans le message : `{email}`, `{date}`, `{app}`.

## Vérification des adresses

Page « Vérifier des emails » (les deux rôles) et bouton « Vérifier » dans le formulaire de liste :

| Contrôle | Résultat |
|---|---|
| Syntaxe RFC | `Syntaxe invalide` |
| Domaine existant (DNS) | `Domaine inexistant` |
| Serveur mail du domaine (MX) | `Valide` / `Risqué` |
| Fournisseurs jetables | `Adresse jetable` |
| Faute de frappe (gmial.com, orange.fe…) | `Faute de frappe ?` + correction en un clic |
| Rejet définitif (550 / 5.1.1) lors d'un envoi | `Boîte inexistante` → écartée des prochains envois |

L'existence d'une boîte précise ne peut pas être garantie avant l'envoi : Orange, Gmail, etc. ne la révèlent pas. Elle est confirmée par le premier envoi.

## Formulaire de réponse (avis, inscription…)

- Dans **Admin > Messages**, la section « Formulaire de réponse » permet de paramétrer : le titre, l'introduction, le texte du bouton de l'email, le bouton d'envoi, le message de remerciement et **les champs** (libellé, type, obligatoire, options). Types disponibles : texte, texte long, note 1-5, oui/non, choix unique, liste, choix multiples, nombre, date, téléphone, email.
- Chaque email contient un **lien personnel** `/avis/{jeton}`. Le jeton identifie le destinataire : il ne saisit pas son adresse, et l'adresse n'apparaît pas dans le lien. Il peut modifier sa réponse en rouvrant le lien.
- **Réponses reçues** : le propet voit uniquement les réponses à ses listes. L'admin voit toutes les réponses, avec un filtre par propet. Pour chaque liste : taux de réponse, moyenne des notes, répartition des choix, export CSV.

## Vues (pages publiques autonomes)

Dans **Admin > Vues**, l'admin crée des pages publiques indépendantes des campagnes (inscription, contact, avis…) :

- Chaque vue a une **adresse publique** `/vue/{slug}` dérivée de son titre. Tant qu'elle est en **brouillon**, l'adresse renvoie une 404.
- Les champs se paramètrent avec le **même constructeur** que les formulaires d'email (types, obligatoire, options, réordonnancement).
- La page s'affiche dans le **thème public** (carte centrée, bandeau orange, bouton foncé), avec le logo/les couleurs de la présentation.
- Les réponses sont **anonymes** (ni compte, ni email obligatoire) et consultables dans **Admin > Vues > Réponses**, avec export CSV.

Les pages publiques (formulaire d'avis, vues, connexion) partagent ce thème : voir `resources/views/layouts/public.blade.php` et la section « Thème public » de `public/css/app.css`.

## Messages, présentation et comportement

- **Admin > Messages** : plusieurs messages, chacun avec son logo (facultatif), un bouton d'action et l'option « Disponible pour les propets ». Le propet choisit le message au moment de soumettre sa liste, avec un aperçu exact.
- **Admin > Présentation** : logo général (intégré à l'email en pièce jointe inline, pas de lien externe), **texte à côté du logo** (nom d'expéditeur façon « Le service clients »), **bloc d'informations à libellés** (lignes « libellé : valeur », variables `{email}` `{date}` `{app}`), couleurs, police, en-tête, pied de page, avec un aperçu en direct.
- **Admin > Paramètres** : validation obligatoire ou non, écarter les adresses invalides et les fautes de frappe, **n'envoyer qu'aux destinataires Orange** (domaines paramétrables, par défaut `orange.fr, wanadoo.fr` ; les autres sont marquées « Ignoré / Hors Orange » et ne reçoivent rien), débit par minute, taille max, nom d'expéditeur, adresse de réponse.

## Démarrage (XAMPP / Windows)

```powershell
$env:Path = "C:\xampp\php;" + $env:Path
cd mailer-app
php artisan migrate --seed      # crée les tables et les comptes de démo
php artisan serve               # http://localhost:8000
php artisan queue:work          # dans un 2e terminal : c'est lui qui envoie les emails
```

Comptes de démo (à changer tout de suite) :

| Rôle   | Email              | Mot de passe |
|--------|--------------------|--------------|
| Admin  | admin@example.com  | password     |
| Propet | propet@example.com | password     |

## Envoi réel (SMTP)

Par défaut `MAIL_MAILER=log` : les emails sont écrits dans `storage/logs/laravel.log`. Pour un vrai envoi, modifiez `.env` :

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.votre-fournisseur.fr
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_SCHEME=null
MAIL_FROM_ADDRESS="contact@votre-domaine.fr"
MAIL_FROM_NAME="Votre organisation"
```

Utilisez un domaine expéditeur avec SPF, DKIM et DMARC configurés, sinon Orange, Gmail, etc. rejetteront les messages ou les classeront en spam.

En production, faites tourner `php artisan queue:work` en continu (Supervisor, ou une tâche planifiée Windows).

## Tests

```powershell
php artisan test
```

## RGPD

L'envoi de messages commerciaux à des particuliers exige leur **consentement préalable** (CNIL / article L34-5 du CPCE). Les adresses professionnelles peuvent être contactées si le message concerne leur activité et qu'un moyen d'opposition est proposé (le lien de désinscription). Ne soumettez que des listes dont l'origine est légitime.
