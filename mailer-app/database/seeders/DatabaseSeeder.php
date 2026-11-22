<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\MessageTemplate;
use App\Models\Page;
use App\Models\User;
use App\Support\ResponseForm;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Administrateur',
            'password' => 'password',
            'role' => Role::Admin,
            'is_active' => true,
        ]);

        User::updateOrCreate(['email' => 'propet@example.com'], [
            'name' => 'Propet Démo',
            'password' => 'password',
            'role' => Role::Propet,
            'is_active' => true,
        ]);

        if (MessageTemplate::count() === 0) {
            MessageTemplate::create([
                'name' => 'Message par défaut',
                'subject' => 'Information de {app}',
                'body' => "Bonjour,\n\nNous vous contactons au sujet de ...\n\n(Modifiez ce texte dans Admin > Messages.)\n\nCordialement,\nL'équipe {app}",
                'is_default' => true,
                'is_active' => true,
            ]);

            MessageTemplate::create([
                'name' => 'Invitation',
                'subject' => 'Vous êtes invité — {app}',
                'body' => "Bonjour,\n\nNous avons le plaisir de vous inviter à ...\n\nÀ très bientôt,\nL'équipe {app}",
                'button_text' => 'Je confirme ma présence',
                'button_url' => 'https://example.com/inscription',
                'is_active' => true,
            ]);

            MessageTemplate::create([
                'name' => "Demande d'avis",
                'subject' => 'Votre avis compte — {app}',
                'body' => "Bonjour,\n\nNous aimerions connaître votre avis. Cela ne prend qu'une minute : cliquez sur le bouton ci-dessous.\n\nMerci d'avance,\nL'équipe {app}",
                'is_active' => true,
                'form' => ResponseForm::normalize([
                    'enabled' => true,
                    'title' => 'Donnez-nous votre avis',
                    'intro' => 'Vos réponses nous aident à nous améliorer.',
                    'button_text' => 'Donner mon avis',
                    'submit_label' => 'Envoyer mon avis',
                    'thanks' => 'Merci pour votre avis !',
                    'fields' => [
                        ['label' => 'Votre note globale', 'type' => 'rating', 'required' => true],
                        ['label' => 'Nous recommanderiez-vous ?', 'type' => 'yesno', 'required' => true],
                        ['label' => 'Ce qui vous intéresse', 'type' => 'checkbox', 'options' => "Information\nÉvénements\nPartenariat"],
                        ['label' => 'Votre commentaire', 'type' => 'textarea', 'placeholder' => 'Écrivez ici…'],
                    ],
                ]),
            ]);
        }

        if (Page::count() === 0) {
            $form = ResponseForm::normalize([
                'enabled' => true,
                'title' => 'Inscription à la newsletter',
                'intro' => "Laissez-nous votre adresse pour recevoir nos informations.",
                'submit_label' => 'Je m’inscris',
                'thanks' => 'Votre inscription est enregistrée, merci !',
                'fields' => [
                    ['label' => 'Votre nom', 'type' => 'text', 'required' => true],
                    ['label' => 'Votre adresse email', 'type' => 'email', 'required' => true],
                    ['label' => "Centres d'intérêt", 'type' => 'checkbox', 'options' => "Actualités\nÉvénements\nOffres"],
                ],
            ]);

            Page::create([
                'slug' => Str::slug($form['title']),
                'form' => $form,
                'is_published' => true,
            ]);
        }
    }
}
