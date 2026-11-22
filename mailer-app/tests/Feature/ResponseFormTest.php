<?php

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\RecipientStatus;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\MessageTemplate;
use App\Models\Response;
use App\Models\Setting;
use App\Models\User;
use App\Support\ResponseForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponseFormTest extends TestCase
{
    use RefreshDatabase;

    private MessageTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->template = MessageTemplate::create([
            'name' => 'Avis', 'subject' => 'Votre avis', 'body' => "Bonjour,\nRépondez ici : {lien}", 'is_active' => true, 'is_default' => true,
            'form' => ResponseForm::normalize([
                'enabled' => true, 'title' => 'Votre avis', 'button_text' => 'Donner mon avis', 'thanks' => 'Merci beaucoup',
                'fields' => [
                    ['label' => 'Note du service', 'type' => 'rating', 'required' => true],
                    ['label' => 'Recommanderiez-vous ?', 'type' => 'yesno', 'required' => true],
                    ['label' => 'Thèmes', 'type' => 'checkbox', 'options' => "Culture\nSport"],
                    ['label' => 'Commentaire', 'type' => 'textarea'],
                ],
            ]),
        ]);
    }

    private function makeCampaign(User $owner, string $email = 'jean.dupont@example.fr'): CampaignRecipient
    {
        $c = Campaign::create([
            'user_id' => $owner->id, 'message_template_id' => $this->template->id, 'title' => 'Liste de '.$owner->name,
            'subject' => 's', 'body' => 'b', 'form' => $this->template->form, 'status' => CampaignStatus::Completed, 'total' => 1,
        ]);

        return $c->recipients()->create(['email' => $email, 'status' => RecipientStatus::Sent]);
    }

    public function test_admin_configures_form_field_names(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.templates.update', $this->template), [
            'name' => 'Avis', 'subject' => 'S', 'body' => 'B', 'is_active' => '1',
            'form' => [
                'enabled' => '1', 'title' => 'Inscription', 'button_text' => "Je m'inscris",
                'fields' => [
                    ['label' => 'Nom de la structure', 'type' => 'text', 'required' => '1'],
                    ['label' => 'Nombre de participants', 'type' => 'number'],
                    ['label' => 'Créneau', 'type' => 'radio', 'options' => "Matin\nAprès-midi"],
                    ['label' => 'Choix vide', 'type' => 'select', 'options' => ''],   // ignoré : pas d'option
                    ['label' => '', 'type' => 'text'],                               // ignoré : sans libellé
                    ['label' => 'Nom de la structure', 'type' => 'text'],            // doublon → clé unique
                ],
            ],
        ])->assertSessionHasNoErrors();

        $form = $this->template->fresh()->form;
        $this->assertTrue($form['enabled']);
        $this->assertSame('Inscription', $form['title']);
        $this->assertSame(['nom_de_la_structure', 'nombre_de_participants', 'creneau', 'nom_de_la_structure_2'], array_column($form['fields'], 'key'));
        $this->assertSame(['Matin', 'Après-midi'], $form['fields'][2]['options']);
        $this->assertTrue($form['fields'][0]['required']);

        $this->actingAs($admin)->get(route('admin.templates.edit', $this->template))->assertOk()->assertSee('Nom de la structure');
        $this->actingAs($admin)->get(route('preview.form', $this->template))->assertOk()->assertSee('Nombre de participants')->assertSee('Matin');
    }

    public function test_email_contains_personal_link_without_the_address(): void
    {
        Setting::put('require_approval', '0');
        $propet = User::factory()->create();

        $this->actingAs($propet)->post('/propet/campagnes', [
            'title' => 'Avis', 'message_template_id' => $this->template->id, 'emails' => 'jean.dupont@example.fr',
        ])->assertRedirect();

        $recipient = CampaignRecipient::firstOrFail();
        $this->assertSame(48, strlen($recipient->token));

        $sent = app('mailer')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
        $html = $sent->getHtmlBody();
        $link = route('form.show', $recipient->token);

        $this->assertStringContainsString($link, $html);                       // bouton
        $this->assertStringContainsString('Donner mon avis', $html);
        $this->assertStringContainsString($link, $sent->getTextBody());        // {lien} dans le texte
        $this->assertStringNotContainsString('jean.dupont', parse_url($link, PHP_URL_PATH) . parse_url($link, PHP_URL_QUERY));
    }

    public function test_recipient_answers_without_typing_email_and_can_edit(): void
    {
        $r = $this->makeCampaign(User::factory()->create());

        // La page reconnaît le destinataire, sans champ email à remplir
        $page = $this->get(route('form.show', $r->token))->assertOk()
            ->assertSee('j*********t@example.fr')
            ->assertDontSee('jean.dupont@example.fr')
            ->assertSee('Note du service');
        $this->assertStringNotContainsString('name="email"', $page->getContent());
        $this->assertNotNull($r->fresh()->form_opened_at);

        // Validation des champs paramétrés
        $this->post(route('form.store', $r->token), ['answers' => ['note_du_service' => 9]])
            ->assertSessionHasErrors(['answers.note_du_service', 'answers.recommanderiez_vous']);

        // Même si quelqu'un tente d'envoyer une autre adresse, c'est celle du jeton qui est gardée
        $this->post(route('form.store', $r->token), [
            'email' => 'pirate@example.fr',
            'answers' => ['note_du_service' => 4, 'recommanderiez_vous' => 'oui', 'themes' => ['Sport'], 'commentaire' => 'Très bien'],
        ])->assertRedirect(route('form.show', $r->token));

        $resp = Response::firstOrFail();
        $this->assertSame('jean.dupont@example.fr', $resp->email);
        $this->assertSame('Note du service', $resp->answers[0]['label']);
        $this->assertSame(['Sport'], $resp->answers[2]['value']);

        $this->get(route('form.show', $r->token))->assertSee('Merci beaucoup');            // juste après l'envoi
        $this->get(route('form.show', $r->token))->assertSee('Vous avez déjà répondu');    // en rouvrant le lien

        // Modification : une seule réponse par destinataire
        $this->post(route('form.store', $r->token), ['answers' => ['note_du_service' => 5, 'recommanderiez_vous' => 'non']]);
        $this->assertSame(1, Response::count());
        $this->assertSame(1, Response::first()->edits);
        $this->assertEquals(5, Response::first()->answers[0]['value']);

        // Jeton invalide
        $this->get('/avis/'.str_repeat('a', 48))->assertNotFound();
    }

    public function test_answer_redirects_when_the_admin_sets_a_redirect_url(): void
    {
        $r = $this->makeCampaign(User::factory()->create());
        $r->campaign->update(['form' => array_merge($r->campaign->form, ['redirect_url' => 'https://exemple.fr/suite'])]);

        $this->post(route('form.store', $r->token), [
            'answers' => ['note_du_service' => 4, 'recommanderiez_vous' => 'oui'],
        ])->assertRedirect('https://exemple.fr/suite');

        $this->assertSame(1, Response::count()); // réponse enregistrée avant la redirection
    }

    public function test_propet_sees_only_responses_to_own_lists(): void
    {
        $admin = User::factory()->admin()->create();
        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);

        foreach ([[$alice, 'client-alice@example.fr', 'Avis Alice'], [$bob, 'client-bob@example.fr', 'Avis Bob']] as [$u, $email, $comment]) {
            $r = $this->makeCampaign($u, $email);
            $this->post(route('form.store', $r->token), ['answers' => ['note_du_service' => 3, 'recommanderiez_vous' => 'oui', 'commentaire' => $comment]]);
        }
        $this->assertSame(2, Response::count());

        $this->actingAs($alice)->get('/propet/reponses')->assertOk()
            ->assertSee('client-alice@example.fr')->assertSee('Avis Alice')
            ->assertDontSee('client-bob@example.fr')->assertDontSee('Avis Bob');

        $bobCampaign = Campaign::where('user_id', $bob->id)->first();
        $this->actingAs($alice)->get('/propet/reponses?campaign='.$bobCampaign->id)->assertDontSee('Avis Bob');

        $csv = $this->actingAs($alice)->get('/propet/reponses/export')->streamedContent();
        $this->assertStringContainsString('Avis Alice', $csv);
        $this->assertStringNotContainsString('Avis Bob', $csv);

        $this->actingAs($alice)->get('/admin/reponses')->assertForbidden();

        $this->actingAs($admin)->get('/admin/reponses')->assertOk()->assertSee('Avis Alice')->assertSee('Avis Bob');
        $this->actingAs($admin)->get('/admin/reponses?propet='.$bob->id)->assertSee('Avis Bob')->assertDontSee('Avis Alice');

        // Synthèse par liste + colonne "Répondu" sur le détail
        $aliceCampaign = Campaign::where('user_id', $alice->id)->first();
        $this->actingAs($alice)->get('/propet/reponses?campaign='.$aliceCampaign->id)->assertOk()->assertSee('Taux de réponse');
        $this->actingAs($alice)->get(route('propet.campaigns.show', $aliceCampaign))->assertOk()->assertSee('Répondu')->assertSee('Voir les réponses');
    }

    public function test_confirmation_modal_replaces_browser_confirm(): void
    {
        $admin = User::factory()->admin()->create();
        $c = Campaign::create([
            'user_id' => User::factory()->create()->id, 'message_template_id' => $this->template->id, 'title' => 'X',
            'subject' => 's', 'body' => 'b', 'status' => CampaignStatus::Pending, 'total' => 0,
        ]);

        $html = $this->actingAs($admin)->get(route('admin.campaigns.show', $c))->assertOk()
            ->assertSee('id="confirmModal"', false)
            ->assertSee("data-confirm-title=\"Valider et lancer l'envoi ?\"", false)
            ->getContent();
        $this->assertStringNotContainsString('return confirm(', $html);

        $this->actingAs(User::factory()->create())->get('/propet/campagnes/nouvelle')->assertSee('data-confirm-message-fn="submitMessage"', false);
    }
}
